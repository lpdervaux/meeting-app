<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfilFormType;
use App\Repository\UserRepository;
use App\Security\AppAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security\UserAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class ProfilController extends AbstractController
{
    #[Route('/profil/details/{id}', name: 'app_details', methods: ['GET', 'POST'])]

    public function listDetails(int $id, UserRepository $userRepository): Response
    {

        // récupère ce user en fonction de l'id présent dans l'URL
        $user = $userRepository->find($id);

        // s'il n'existe pas en bdd, on déclenche une erreur 404
        if (!$user){
            throw $this->createNotFoundException('This user do not exists! Sorry!');
        }


        return $this->render('profil/details.html.twig', [
            //les passe à Twig
            "user" => $user
        ]);
    }

    #[Route('/profil/edit/{id}', name: 'app_profil_edit', methods: ['GET', 'POST'])]

    public function edit(User $user, Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, AppAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        {

            if ($this->getUser() !== $user){
                return $this->redirectToRoute('app_login');
            }

            $form = $this->createForm(ProfilFormType::class, $user);
            $form->handleRequest($request);


            if ($form->isSubmitted() && $form->isValid()) {

                // encode the plain password
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'Votre profil a bien été mis à jour');
                // do anything else you need here, like send an email


            }

            return $this->render('profil/edit.html.twig', [
                'profilEditForm' => $form->createView(),
            ]);
        }
    }
}
