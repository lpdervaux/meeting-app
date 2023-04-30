<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Meetup;
use App\Entity\MeetupStatus;
use App\Entity\User;
use App\Form\MeetupDesistType;
use App\Form\MeetupCancelType;
use App\Form\MeetupRegisterType;
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

        $oneMonthFromEnd = $meetup
            ->getEnd()
            ->add(new \DateInterval('P1M'));

        if ( $now > $oneMonthFromEnd )
            $response = $this->render('meetup/archived.html.twig');
        else
        {
            $status = $meetup->getStatus($now);
            $attending = $meetup
                ->getAttendees()
                ->contains($user);

            $registerEnabled =
                ( $status === MeetupStatus::Open )
                && ( ! $attending )
                && ( $meetup->getAttendees()->count() < $meetup->getCapacity() );
            $desistEnabled =
                ( $status === MeetupStatus::Open )
                && ( $attending );
            $cancelEnabled =
                ( ! $meetup->isCancelled() )
                && (
                    $user === $meetup->getCoordinator()
                    || $this->isGranted('ROLE_ADMINISTRATOR')
                );

            $cancelAlert =
                ( $meetup->isCancelled() )
                && ( $now < $meetup->getEnd() );

            $registerForm = $this->createForm(MeetupRegisterType::class);
            $desistForm = $this->createForm(MeetupDesistType::class);
            $cancelForm = $this->createForm(MeetupCancelType::class);

            $registerForm->handleRequest($request);
            if ( $registerForm->isSubmitted() && $registerForm->isValid() )
            {
                if ( $registerEnabled )
                {
                    $meetup->addAttendee($user);
                    $entityManager->persist($meetup);
                    $entityManager->flush();

                    $this->addFlash('success', 'Inscription réussie');

                    $registerEnabled = false;
                    $desistEnabled = true;
                }
                else
                    $this->addFlash('warning', 'Inscription échouée');
            }

            $desistForm->handleRequest($request);
            if ( $desistForm->isSubmitted() && $desistForm->isValid() )
            {
                if ( $desistEnabled )
                {
                    $meetup->removeAttendee($user);
                    $entityManager->persist($meetup);
                    $entityManager->flush();

                    $this->addFlash('success', 'Désistement réussi');

                    $desistEnabled = false;
                    $registerEnabled = true;
                }
                else
                    $this->addFlash('warning', 'Désistement échoué');
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
                    'desistFormView' => $desistForm->createView(),
                    'cancelFormView' => $cancelForm->createView(),

                    'registerEnabled' => $registerEnabled,
                    'cancelEnabled' => $cancelEnabled,
                    'desistEnabled' => $desistEnabled,

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
