<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Meetup;
use App\Entity\MeetupStatus;
use App\Entity\User;
use App\Form\MeetupCancelType;
use App\Form\MeetupRegisterUserType;
use App\Form\MeetupType;
use App\Repository\MeetupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/meetup', name: 'app_meetup')]
class MeetupController extends AbstractController
{
    #[Route(
        '/{id}',
        name: '_details',
        requirements: [ 'id' => '^\d+$' ]
    )]
    public function details (
        int $id,
        Request $request,
        MeetupRepository $meetupRepository,
        EntityManagerInterface $entityManager
    ) : Response
    {
        $now = new \DateTimeImmutable();

        $meetup = $meetupRepository->findDetails($id);
        if ( ! $meetup )
            throw $this->createNotFoundException();
        $user = $this->getUser();
        if ( ! $user instanceof User )
            throw $this->createAccessDeniedException();

        if ( $now > $meetup->getEnd()->add(new \DateInterval('P1M')) )
            $response = $this->render('meetup/archived.html.twig');
        else
        {
            $registrationEnabled = $meetup->canRegister($user);
            $cancelEnabled = ( ! $meetup->isCancelled() )
                && ( $user === $meetup->getCoordinator() || $this->isGranted('ROLE_ADMINISTRATOR') );
            $cancelAlert = ( $meetup->isCancelled() )
                && ( $now < $meetup->getEnd() );

            $registerForm = $this->createForm(MeetupRegisterUserType::class);
            $cancelForm = $this->createForm(MeetupCancelType::class);

            $registerForm->handleRequest($request);
            if ( $registerForm->isSubmitted() && $registerForm->isValid() )
            {
                if ( $registrationEnabled )
                {
                    $meetup->addAttendee($user);
                    $entityManager->persist($meetup);
                    $entityManager->flush();

                    $this->addFlash('success', 'Inscription réussie');

                    $registrationEnabled = false;
                }
                else
                    $this->addFlash('warning', 'Inscription échouée');
            }

            $cancelForm->handleRequest($request);
            if ( $cancelForm->isSubmitted() && $cancelForm->isValid() )
            {
                if ( $cancelEnabled )
                {
                    $meetup->setCancelled(true);
                    $meetup->setCancellationDate(new \DateTimeImmutable());
                    $meetup->setCancellationReason($cancelForm->getData()['cancellationReason']);
                    $entityManager->persist($meetup);
                    $entityManager->flush($meetup);

                    $cancelEnabled = false;
                }
            }

            $response = $this->render(
                'meetup/details.html.twig',
                [
                    'meetup' => $meetup,

                    'registerFormView' => $registerForm->createView(),
                    'cancelFormView' => $cancelForm->createView(),

                    'registrationEnabled' => $registrationEnabled,
                    'cancelEnabled' => $cancelEnabled,
                    'cancelAlert' => $cancelAlert
                ]
            );
        }

        return $response;
    }

    #[Route('/new', name: '_new')]
    public function new (Request $request, EntityManagerInterface $entityManager) : Response
    {
        $user = $this->getUser();
        if ( ! $user instanceof User )
            throw $this->createAccessDeniedException();

        $meetup = new Meetup();
        $meetup->setCoordinator($user);
        $meetup->addAttendee($user);
        $meetup->setCancelled(false);

        $form = $this->createForm(MeetupType::class, $meetup);
        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() )
        {
            $entityManager->persist($meetup);
            $entityManager->flush();

            $response = $this->redirectToRoute(
                'app_meetup_details',
                [ 'id' => $meetup->getId() ]
            );
        }
        else
            $response = $this->render(
                'meetup/new.html.twig',
                [ 'newMeetupFormView' => $form->createView() ]
            );

        return $response;
    }
}
