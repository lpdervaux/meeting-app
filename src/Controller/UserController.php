<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use mysql_xdevapi\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @method getDoctrine()
 */
class UserController extends AbstractController
{
    #[Route('/profile', name: 'app_user_list')]
    public function list(UserRepository $userRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $userRepository->createQueryBuilder('u')
            ->orderBy('u.nickname', 'ASC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('profile/admin/list.html.twig', [
            'pagination' => $pagination,
            'current_page' => $pagination->getCurrentPageNumber(),
        ]);

    }


    #[Route('/profile/{id}', name: 'app_user_profile', methods:['GET'])]

    public function profil(int $id, UserRepository $userRepository, Security $security): Response
    {

        $user = $userRepository->find($id);

        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        } elseif (!$user) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('profile/details.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods:['GET', "POST"])]

    public function new(RoleRepository $roleRepository,Request $request,  EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $userRole = $roleRepository->findOneBy(['role' => 'ROLE_USER']);
            $user->addRole($userRole);

            $this->handleFormSubmission($user, $form, $entityManager, $passwordHasher);
            $this->addFlash('success', 'Nouvel utilisateur créé avec succès.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('profile/admin/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/{id}/edit', name: 'app_user_edit', methods:['GET', "POST"])]

    public function edit(Security $security, Request $request, User $user,  EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        if (!$security->isGranted('ROLE_ADMINISTRATOR')) {
            // Vérifier si l'utilisateur connecté est bien le propriétaire du profil
            if ($security->getUser() !== $user) {
                return $this->redirectToRoute('app_home');
            }
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifie si l'email de l'utilisateur a été modifié
            $formData = $form->getData();
            $email = $formData->getEmail();
            if ($email !== $user->getEmail() && $entityManager->getRepository(User::class)->findOneBy(['email' => $email])) {
                $this->addFlash('error', 'Cet email est déjà utilisé par un autre utilisateur.');
            }

            $this->handleFormSubmission($user, $form, $entityManager, $passwordHasher);
            $this->addFlash('success', 'Modification réussie !');

            return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
        }

        return $this->render('profile/edit.html.twig', [
            'profilEditForm' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * Handles form submission logic for new and edit actions.
     */
    private function handleFormSubmission(User $user, FormInterface $form, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): void
    {
            // Hash the user's password
            $password = $form->get('plainPassword')->getData();

            if ($password !== null) {
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            }

            $entityManager->persist($user);
            $entityManager->flush();
    }

    #[Route('/profile/{id}', name: 'app_user_delete', methods: ['POST', 'DELETE'])]
    public function delete(EntityManagerInterface $entityManager, UserRepository $userRepository, int $id): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        } elseif (in_array('ROLE_ADMINISTRATOR', $user->getRoles())) {

            $this->addFlash('error', 'Impossible de supprimer un administrateur !');
            return $this->redirectToRoute('app_user_list');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimer avec succès !');

        return $this->redirectToRoute('app_user_list');
    }

    #[Route('/profile/{id}/ban', name: 'app_user_ban', methods: ['POST'])]
    public function ban(EntityManagerInterface $entityManager, UserRepository $userRepository, int $id): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        } elseif (in_array('ROLE_ADMINISTRATOR', $user->getRoles())) {
            $this->addFlash('error', 'Impossible de bannir un administrateur !');

            return $this->redirectToRoute('app_user_list');
        }

        $user->setActive(false);
        $entityManager->flush();
        $this->addFlash('success', 'Utilisateur banni avec succès !');

        return $this->redirectToRoute('app_user_list');
    }

    #[Route('/profile/{id}/unban', name: 'app_user_unban', methods: ['POST'])]
    public function unban(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setActive(true);
        $entityManager->flush();
        $this->addFlash('success', 'L\'utilisateur a été réactivé avec succès !');

        return $this->redirectToRoute('app_user_list');
    }

    #[Route('/insert', name: 'app_user_insert')]
    public function insert(EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $error = null;
        $userAttributes =
            [
                'nickname',
                'name',
                'surname',
                'phoneNumber',
                'email',
                'password',
                'campus'
            ];

        $file = "../data/test.csv";
        $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
        $normalizers = [new ObjectNormalizer()];
        $encoder = [
            new CsvEncoder()
        ];
        $serializer = new Serializer($normalizers, $encoder);
        $fileString = file_get_contents($file);
        $data = $serializer->decode($fileString, $fileExtension);

        $error = $this->checkColumnName($userAttributes,$data)
            .$this->checkColumnName($userAttributes,$data);

        return $this->render('profile/admin/insert.html.twig', [
            'error' => $error
        ]);

    }

    private function checkColumnName($userAttributes, $data)
    {
        $counter= 0;
        $error = 'Les colonnes de votre tableau doivent contenir :';
        foreach ($userAttributes as $ua) {$error = $error.", ".$ua;}
        $error = $error.'. Il vous manque : ';

        foreach($userAttributes as $key1 => $ua)
        {
            $check = false;
            foreach($data[0] as $key2 => $d)
            {
                if(strtolower(trim($ua)) == strtolower(trim($key2)) )
                {
                    $counter++;
                    $check = true;
                }
            }
            if(!$check)
            {
                $error= $error.$ua.", ";
            }
        }

        $error=substr_replace($error, '.', strlen($error)-2, 2 );

        if($counter== count($userAttributes)) {return null;}
        else return $error;
    }

    private function checkTableContents()
    {

    }


}
