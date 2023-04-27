<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @method getDoctrine()
 */
class UserController extends AbstractController
{
    #[Route('/profil', name: 'app_user_list')]
    public function list(UserRepository $userRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $userRepository->createQueryBuilder('u')
            ->orderBy('u.nickname', 'ASC') // Tri par ordre alphabétique croissant du pseudo
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('profil/admin/list.html.twig', [
            'pagination' => $pagination,
            'current_page' => $pagination->getCurrentPageNumber(),
        ]);

    }


    #[Route('/profil/{id}', name: 'app_user_profile', methods:['GET'])]

    public function profil(int $id, UserRepository $userRepository): Response
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

    #[Route('/new', name: 'admin_user_new', methods:['GET', "POST"])]

    public function new(RoleRepository $roleRepository,Request $request,  EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Ajout du rôle "user" par défaut
            $userRole = $roleRepository->findOneBy(['role' => 'ROLE_USER']);
            $user->addRole($userRole);

            $this->handleFormSubmission($user, $form, $entityManager, $passwordHasher);

            return $this->redirectToRoute('app_login');
        }

        return $this->render('profil/admin/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profil/{id}/edit', name: 'app_user_edit', methods:['GET', "POST"])]

    public function edit(Security $security,Request $request, User $user,  EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Vérifie si l'utilisateur est connecté
        if (!$security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifie si l'email de l'utilisateur a été modifié
            $formData = $form->getData();
            $email = $formData->getEmail();
            if ($email !== $user->getEmail() && $entityManager->getRepository(User::class)->findOneBy(['email' => $email])) {
                $this->addFlash('error', 'Cet email est déjà utilisé par un autre utilisateur.');
                return $this->redirectToRoute('app_user_edit');
            }

            $this->handleFormSubmission($user, $form, $entityManager, $passwordHasher);

            return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
        }

        return $this->render('profil/edit.html.twig', [
            'profilEditForm' => $form->createView(),
            'user' => $user,
        ]);

    }

    /**
     * Handles form submission logic for new and edit actions.
     */
    private function handleFormSubmission(User $user, FormInterface $form, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): void
    {
            // Hash the user's password
            $password = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Nouvel utilisateur créé avec succès.');

    }

    #[Route('/profil/{id}', name: 'app_user_delete', methods: ['POST', 'DELETE'])]
    public function delete(EntityManagerInterface $entityManager, UserRepository $userRepository, int $id): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'User has been deleted successfully');

        return $this->redirectToRoute('app_user_list');
    }

}
