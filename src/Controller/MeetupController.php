<?php

declare(strict_types=1);
namespace App\Controller;

use App\Entity\Campus;
use App\Repository\CampusRepository;
use App\Repository\MeetupRepository;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use App\Entity\Meetup;
use App\Entity\MeetupStatus;
use App\Entity\User;
use App\Form\MeetupType;
use App\Form\MeetupDetailsType;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\When;



#[Route('/meetup', name : 'app_meetup')]
class MeetupController extends AbstractController
{
    #[Route('/meetup', name: '_list')]
    public function list(Request $request, MeetupRepository $meetupRepository, CampusRepository $campusRepository): Response
    {
        define('DEFAULT_CAMPUS', $campusRepository->findNameByNo(0));

        $session = $request->getSession();
        $form = $this->generateForm($campusRepository, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
            $session->set('filters', $filters);
            $meetupList = $meetupRepository->findWithFilters($filters, $this->getUser());
            dump($meetupList);

            $form = $this->generateForm($campusRepository, $session);
        } else {
            $filters = array(
                'campus' =>
                    $session->get('filters') == null ?
                        $campusRepository->findByName(DEFAULT_CAMPUS)['campus'] :
                        $session->get('filters')['campus'],
                'research' =>
                    $session->get('filters') == null ?
                        null :
                        $session->get('filters')['research'],
                'start' =>
                    $session->get('filters') == null ?
                        null :
                        $session->get('filters')['start'],
                'end' =>
                    $session->get('filters') == null ?
                        null :
                        $session->get('filters')['end'],
                'coordinator' =>
                    $session->get('filters') == null ?
                        true :
                        $session->get('filters')['coordinator'],
                'registered' =>
                    $session->get('filters') == null ?
                        true :
                        $session->get('filters')['registered'],
                'no_registered' =>
                    $session->get('filters') == null ?
                        true :
                        $session->get('filters')['no_registered'],
                'past' =>
                    $session->get('filters') == null ?
                        false :
                        $session->get('filters')['past']
            );
            $session->set('filters', $filters);
            $meetupList = $meetupRepository->findWithFilters($filters, $this->getUser());
        }

        return $this->render('meetup/index.html.twig', [
            'form' => $form,
            'meetup_list' => $meetupList
        ]);
    }

    private function generateForm($campusRepository, $session)
    {
        return $this->createFormBuilder()
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'label' => 'Campus : ',
                'choice_label' => 'name',
                'choice_attr' => [
                    $session->get('filters') == null ?
                        $campusRepository->findByName(DEFAULT_CAMPUS)['no'] :
                        $campusRepository->findByName($session->get('filters')['campus']->getName())['no']
                    => ['selected' => true]
                ]
            ])
            ->add('research', TextType::class, [
                'label' => 'Le nom de la sortie contient : ',
                'required' => false,
                'attr' => [
                    'value' =>
                        $session->get('filters') == null ?
                            null :
                            $session->get('filters')['research']
                ]
            ])
            ->add('start', DateType::class, [
                'label' => 'Entre : ',
                'html5' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'data' =>
                    $session->get('filters') == null ?
                        null :
                        $session->get('filters')['start'],
                'constraints' => [
                    new When(
                        [
                            'expression' => 'this.getParent()["end"].getData() != null && value == null',
                            'constraints' => [
                                new NotBlank(
                                    [
                                        'message' => 'Compléter la date de début.'
                                    ]
                                ),
                            ]
                        ]
                    )
                ]
            ])
            ->add('end', DateType::class, [
                'label' => 'et : ',
                'html5' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'data' =>
                    $session->get('filters') == null ?
                        null :
                        $session->get('filters')['end'],
                'constraints' => [
                    new When(
                        [
                            'expression' => 'this.getParent()["start"].getData() != null && value == null',
                            'constraints' => [
                                new NotBlank(
                                    [
                                        'message' => 'Compléter la date de fin.'
                                    ]
                                ),
                            ]
                        ]
                    ),
                    new When(
                        [
                            'expression' => 'this.getParent()["start"].getData() != null && value != null',
                            'constraints' => [
                                new GreaterThan(
                                    [
                                        'propertyPath' => 'parent.all[start].data',
                                        'message' => 'La date de fin doit être supérieure à la date de début.'
                                    ]
                                )
                            ]
                        ]
                    )
                ]
            ])
            ->add('coordinator', CheckboxType::class, [
                'label' => 'Sorties dont je suis l\'organisateur/trice : ',
                'required' => false,
                'attr' => [
                    'checked' =>
                        $session->get('filters') == null ?
                            true :
                            $session->get('filters')['coordinator']
                ]
            ])
            ->add('registered', CheckboxType::class, [
                'label' => 'Sorties auxquelles je suis inscrit/e : ',
                'required' => false,
                'attr' => [
                    'checked' =>
                        $session->get('filters') == null ?
                            true :
                            $session->get('filters')['registered']
                ]
            ])
            ->add('no_registered', CheckboxType::class, [
                'label' => 'Sorties auxquelles je ne suis pas inscrit/e : ',
                'required' => false,
                'attr' => [
                    'checked' =>
                        $session->get('filters') == null ?
                            true :
                            $session->get('filters')['no_registered']
                ]
            ])
            ->add('past', CheckboxType::class, [
                'label' => 'Sorties passées : ',
                'required' => false,
                'attr' => [
                    'checked' =>
                        $session->get('filters') == null ?
                            false :
                            $session->get('filters')['past']
                ]
            ])
            ->getForm();
    }

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
                && ( $now < $meetup->getStart() )
                && ( $this->isGranted('cancel', $meetup) );

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
        $meetup->setCapacity(5);
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
