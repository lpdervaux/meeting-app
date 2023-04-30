<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Meetup;
use App\Entity\MeetupStatus;
use App\Entity\User;
use App\Form\MeetupType;
use App\Form\MeetupDetailsType;
use App\Repository\MeetupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

            $userRegistrable =
                ( $status === MeetupStatus::Open )
                && ( ! $attending )
                && ( $meetup->getAttendees()->count() < $meetup->getCapacity() );
            $userCancellable =
                ( $status === MeetupStatus::Open )
                && ( $attending );
            $cancellable =
                ( ! $meetup->isCancelled() )
                && (
                    $user === $meetup->getCoordinator()
                    || $this->isGranted('ROLE_ADMINISTRATOR')
                );

            $detailsForm = $this->createForm(
                MeetupDetailsType::class,
                $meetup,
                [
                    'attr' => [ 'id' => 'detailsForm' ],
                    'form_attr' => 'detailsForm'
                ]
            );

            $detailsForm->handleRequest($request);
            if ( $detailsForm->isSubmitted() )
            {
                if (
                    $detailsForm
                        ->get('userRegister')
                        ->isClicked()
                    && $userRegistrable
                ) {
                    $meetup->addAttendee($user);
                    $entityManager->persist($meetup);
                    $entityManager->flush();

                    $this->addFlash('success', 'Inscription réussie');
                    $userRegistrable = false;
                    $userCancellable = true;
                }
                else if (
                    $detailsForm
                        ->get('userCancel')
                        ->isClicked()
                    && $userCancellable
                ) {
                    $meetup->removeAttendee($user);
                    $entityManager->persist($meetup);
                    $entityManager->flush();

                    $this->addFlash('success', 'Désistement réussi');
                    $userCancellable = false;
                    $userRegistrable = true;
                }
                else if (
                    $detailsForm
                        ->get('cancel')
                        ->isClicked()
                    && $cancellable
                    && $detailsForm->isValid()
                ) {
                    $meetup->setCancelled(true);
                    $meetup->setCancellationDate(new \DateTimeImmutable());
                    $entityManager->persist($meetup);
                    $entityManager->flush($meetup);

                    $this->addFlash('success', 'Sortie annulée');
                    $cancellable = false;
                }
            }

            $cancelAlert =
                ( $meetup->isCancelled() )
                && ( $now < $meetup->getEnd() );

            $response = $this->render(
                'meetup/details.html.twig',
                [
                    'meetup' => $meetup,
                    'detailsFormView' => $detailsForm->createView(),

                    'userRegistrable' => $userRegistrable,
                    'userCancellable' => $userCancellable,
                    'cancellable' => $cancellable,

                    'cancelAlert' => $cancelAlert,
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
