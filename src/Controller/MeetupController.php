<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Repository\CampusRepository;
use App\Repository\MeetupRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MeetupController extends AbstractController
{
    #[Route('/meetup', name: 'app_meetup_list')]
    public function list(Request $request, MeetupRepository $meetupRepository, CampusRepository $campusRepository): Response
    {
        $form = $this->createFormBuilder()
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'label' => 'Campus : ',
                'choice_label' => 'name'
            ])
            ->add('research', TextType::class , [
                'label' => 'Le nom de la sortie contient : ',
                'required' => false
            ])
            ->add('start', DateType::class, [
                'label' => 'Entre : ',
                'html5'=> true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false
            ])
            ->add('end', DateType::class, [
                'label' => 'et : ',
                'html5'=> true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false
            ])
            ->add('coordinator', CheckboxType::class, [
                'label' => 'Sorties dont je suis l\'organisateur/trice : ',
                'required' => false,
                'attr' => ['checked' => true]
            ])
            ->add('registered', CheckboxType::class, [
                'label' => 'Sorties auxquelles je suis inscrit/e : ',
                'required' => false,
                'attr' => ['checked' => true]
            ])
            ->add('no_registered', CheckboxType::class, [
                'label' => 'Sorties auxquelles je ne suis pas inscrit/e : ',
                'required' => false,
                'attr' => ['checked' => true]
            ])
            ->add('past', CheckboxType::class, [
                'label' => 'Sorties passées : ',
                'required' => false,
                'attr' => ['checked' => false]
            ])
            ->getForm();


        $form->handleRequest($request);


        if($form->isSubmitted() && $form->isValid())
        {
            $filters = $form->getData();
            $meetupList = $meetupRepository->findWithFilters($filters, $this->getUser());
            dump($meetupList);
        }
        else
        {
            $filters = array(
                'campus' => $campusRepository->findWithMinId(),
                'research' => null,
                'start' => null,
                'end' => null,
                'coordinator' => true,
                'registered' => true,
                'no_registered' => true,
                'past' => false
            );
            $meetupList = $meetupRepository->findWithFilters($filters, $this->getUser());
        }

        return $this->render('meetup/index.html.twig', [
            'form' => $form,
            'meetup_list' => $meetupList
        ]);
    }
}
