<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Repository\CampusRepository;
use App\Repository\MeetupRepository;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

define('DEFAULT_CAMPUS', 'Navarro');

class MeetupController extends AbstractController
{
    #[Route('/meetup', name: 'app_meetup_list')]
    public function list(Request $request, MeetupRepository $meetupRepository, CampusRepository $campusRepository): Response
    {
        $session = $request->getSession();
        $form = $this->generateForm($campusRepository, $session);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $filters = $form->getData();
            $session->set('filters', $filters);
            $meetupList = $meetupRepository->findWithFilters($filters, $this->getUser());
            dump($meetupList);

            $form = $this->generateForm($campusRepository, $session);
        }
        else
        {
            $filters = array(
                'campus' =>
                    $session->get('filters') == null?
                        $campusRepository->findByName(DEFAULT_CAMPUS)['campus']:
                        $session->get('filters')['campus'],
                'research' =>
                    $session->get('filters') == null?
                        null:
                        $session->get('filters')['research'],
                'start' =>
                    $session->get('filters') == null?
                        null:
                        $session->get('filters')['start'],
                'end' =>
                    $session->get('filters') == null?
                        null:
                        $session->get('filters')['end'],
                'coordinator' =>
                    $session->get('filters') == null?
                        true:
                        $session->get('filters')['coordinator'],
                'registered' =>
                    $session->get('filters') == null?
                        true:
                        $session->get('filters')['registered'],
                'no_registered' =>
                    $session->get('filters') == null?
                        true:
                        $session->get('filters')['no_registered'],
                'past' =>
                    $session->get('filters') == null?
                        false:
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
                    $session->get('filters') == null?
                        $campusRepository->findByName(DEFAULT_CAMPUS)['no']:
                        $campusRepository->findByName($session->get('filters')['campus']->getName())['no']
                    => ['selected' => true]
                ]
            ])
            ->add('research', TextType::class , [
                'label' => 'Le nom de la sortie contient : ',
                'required' => false,
                'attr' => [
                    'value' =>
                        $session->get('filters') == null?
                            null:
                            $session->get('filters')['research']
                ]
            ])
            ->add('start', DateType::class, [
                'label' => 'Entre : ',
                'html5'=> true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'data' =>
                    $session->get('filters') == null?
                        null:
                        $session->get('filters')['start']
            ])
            ->add('end', DateType::class, [
                'label' => 'et : ',
                'html5'=> true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'data' =>
                    $session->get('filters') == null?
                        null:
                        $session->get('filters')['end']
            ])
            ->add('coordinator', CheckboxType::class, [
                'label' => 'Sorties dont je suis l\'organisateur/trice : ',
                'required' => false,
                'attr' => [
                    'checked' =>
                        $session->get('filters') == null?
                            true:
                            $session->get('filters')['coordinator']
                ]
            ])
            ->add('registered', CheckboxType::class, [
                'label' => 'Sorties auxquelles je suis inscrit/e : ',
                'required' => false,
                'attr' => [
                    'checked' =>
                        $session->get('filters') == null?
                            true:
                            $session->get('filters')['registered']
                ]
            ])
            ->add('no_registered', CheckboxType::class, [
                'label' => 'Sorties auxquelles je ne suis pas inscrit/e : ',
                'required' => false,
                'attr' => [
                    'checked' =>
                        $session->get('filters') == null?
                            true:
                            $session->get('filters')['no_registered']
                ]
            ])
            ->add('past', CheckboxType::class, [
                'label' => 'Sorties passées : ',
                'required' => false,
                'attr' => [
                    'checked' =>
                        $session->get('filters') == null?
                            false:
                            $session->get('filters')['past']
                ]
            ])
            ->getForm();
    }

}
