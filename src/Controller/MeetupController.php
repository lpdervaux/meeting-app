<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Meetup;
use App\Entity\User;
use App\Form\NewMeetupType;
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
    #[Route('/new', name: '_new')]
    public function new (Request $request, EntityManagerInterface $entityManager) : Response
    {
        $user = $this->getUser();
        if ( ! $user instanceof User )
            throw new HttpException(statusCode: 500);

        $meetup = new Meetup();
        $meetup->setCoordinator($user);
        $meetup->setCancelled(false);

        $form = $this->createForm(NewMeetupType::class, $meetup);
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

    #[Route('/details/{id}', name: '_details')]
    public function details (int $id, MeetupRepository $meetupRepository) : Response
    {
        $meetup = $meetupRepository->find($id);
        if ( $meetup )
            $this->createNotFoundException();

        return $this->render(
            'meetup/details.html.twig',
            [ 'meetup' => $meetup ]
        );
    }
}
