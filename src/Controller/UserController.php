<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Entity\Role;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\CampusRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use mysql_xdevapi\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\File;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * @method getDoctrine()
 */
class UserController extends AbstractController
{
    #[Route('/profile', name: 'app_user_list')]
    public function list(UserRepository $userRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $pagination=null;
        $form = $this->createFormBuilder()
            ->add('research', TextType::class, [
                'attr' => [
                    'placeholder' => 'Rechercher un utilisateur par mot clé...'
                ]
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $word = $form->getData()['research'];
            $userList = $userRepository->findByKey($word);

            $pagination = $paginator->paginate(
                $userList,
                $request->query->getInt('page', 1),
                20
            );
        }
        else
        {
            $query = $userRepository->createQueryBuilder('u')
                ->orderBy('u.nickname', 'ASC')
                ->getQuery();

            $pagination = $paginator->paginate(
                $query,
                $request->query->getInt('page', 1),
                20
            );

        }

        return $this->render('profile/admin/list.html.twig', [
            'form' => $form,
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

        $this->addFlash('success', 'Utilisateur supprimé avec succès !');

        return $this->redirectToRoute('app_user_list');
    }

    #[Route('/profile/{id}/ban', name: 'app_user_ban', methods: ['POST'])]
    public function ban(EntityManagerInterface $entityManager, UserRepository $userRepository,RoleRepository $roleRepository, int $id): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        } elseif (in_array('ROLE_ADMINISTRATOR', $user->getRoles())) {
            $this->addFlash('error', 'Impossible de bannir un administrateur !');

            return $this->redirectToRoute('app_user_list');
        }
        $userRole = $roleRepository->findOneBy(['role' => 'ROLE_USER']);
        $user->removeRole($userRole);////////

        $user->setActive(false);
        $entityManager->persist($user);////////
        $entityManager->flush();
        $this->addFlash('success', 'Utilisateur banni avec succès !');

        return $this->redirectToRoute('app_user_list');
    }

    #[Route('/profile/{id}/unban', name: 'app_user_unban', methods: ['POST'])]
    public function unban(UserRepository $userRepository, EntityManagerInterface $entityManager,RoleRepository $roleRepository, int $id): Response
    {
        $user = $userRepository->find($id);/////
        $userRole = $roleRepository->findOneBy(['role' => 'ROLE_USER']);////
        $user->addRole($userRole);////
        $user->setActive(true);
        $entityManager->persist($user);////////
        $entityManager->flush();
        $this->addFlash('success', 'L\'utilisateur a été réactivé avec succès !');


        return $this->redirectToRoute('app_user_list');
    }

    #[Route('/insert', name: 'app_user_insert')]
    public function insert(Request $request, SluggerInterface $slugger, EntityManagerInterface $entityManager, UserRepository $userRepository, CampusRepository $campusRepository,UserPasswordHasherInterface $passwordHasher , RoleRepository $roleRepository): Response
    {
        $error = null;
        $file = null;
        $userAttributes =
            [
                'nickname'    => '#^[a-zA-Z]{1,100}$#',
                'name'        => '#^[a-zA-Zéèàçù]{1,100}$#',
                'surname'     => '#^[a-zA-Zéèàçù]{1,100}$#',
                'phoneNumber' => '#^(0|\+33)[1-9]( *[0-9]{2}){4}$#',
                'email'       => '#^[\w\-\.]+@([\w-]+\.)+[\w-]{2,4}$#',
                'password'    => '#^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{8,}$#',
                'campus'      => '#^[a-zA-Z]{1,255}#'
            ];


        $form = $this->createFormBuilder()
            ->add('insert', FileType::class, [
                'mapped' => false,
                'label'=> 'Insérer votre fichier csv',
                'required' => true,
                'attr' => [
                    'accept' => 'text/csv'
                ]
            ])
            ->getForm();


        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            /** @var UploadedFile $brochureFile */
            $file = $form->get('insert')->getData();


            if ($file)
            {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                if($file->guessExtension() == 'txt')
                {
                    $newFilename = str_replace('.txt','.csv',$newFilename);
                }

                if($file->guessExtension() == 'zip')
                {
                    $newFilename = str_replace('.zip','.csv',$newFilename);
                }

                try {
                    $file->move(
                        $this->getParameter('file_csv_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {

                }

                $file = "../data/".$newFilename;
                $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                $normalizers = [new ObjectNormalizer()];
                $encoder = [
                    new CsvEncoder()
                ];
                $serializer = new Serializer($normalizers, $encoder);
                $fileString = file_get_contents($file);

                if($fileString == null)
                {
                    $error = 'Les colonnes de votre tableau doivent contenir : ';
                    foreach ($userAttributes as $key=>$ua) {$error = $error.$key.", ";}
                    $error=substr_replace($error, '. ', strlen($error)-2, 2 );

                }
                else
                {
                    if($error == null)
                    {
                        $data = $serializer->decode($fileString, $fileExtension);

                        $index = 0;
                        foreach ($userAttributes as $key=>$value)
                        {
                            if($index == 0) $error = $error."Pas de données pour : ";
                            if(empty($data[0][$key])) $error = $error.$key.", ";
                            $index++;
                            if($index == count($userAttributes)) $error=substr_replace($error, '. ', strlen($error)-2, 2 );
                        }

                        if($error == null)
                        {

                            $error = $this->checkColumnName($userAttributes,$data);

                            if($error == null)
                            {
                                $error = $this->checkTableContents($userAttributes, $data, $userRepository , $campusRepository);

                                if($error == null)
                                {
                                    $this->addUsers($data, $entityManager, $campusRepository, $passwordHasher, $roleRepository);
                                    $this->addFlash('success', 'Les utilisateurs ont été créés avec succès !');
                                }
                            }
                        }
                    }
                }
            }
        }

        if(file_exists($file))
        {
            unlink($file);
        }

        return $this->render('profile/admin/insert.html.twig', [
            'error' => $error,
            'form' => $form
        ]);

    }

    private function checkColumnName($userAttributes, $data)
    {
        $counter= 0;
        $error = 'Les colonnes de votre tableau doivent contenir : ';
        foreach ($userAttributes as $key=>$ua) {$error = $error.$key.", ";}
        $error=substr_replace($error, '. ', strlen($error)-2, 2 );
        $error = $error.'Il vous manque : ';

        foreach($userAttributes as $key1 => $ua)
        {
            $check = false;
            foreach($data[0] as $key2 => $d)
            {
                if(strtolower(trim($key1)) == strtolower(trim($key2)) )
                {
                    $counter++;
                    $check = true;
                }
            }
            if(!$check)
            {
                $error= $error.$key1.", ";
            }
        }

        $error=substr_replace($error, '.', strlen($error)-2, 2 );

        if($counter== count($userAttributes)) {return null;}
        else return $error;
    }


    private function checkTableContents($usersAttributes, $data, UserRepository $userRepository, CampusRepository $campusRepository)
    {
        $globalCounter=0;
        $nicknameAndEmailsBDD = $userRepository->findAllNicknameAndEmail();
        $campusBDD = $campusRepository->findAll();
        //dd($campusBDD);
        $error = 'Votre tableau contient des erreurs. ';
        for($i = 0 ; $i<count($data) ; $i++)
        {
            $counter1 = 0;
            foreach($data[$i] as $key=>$value)
            {
                if(!preg_match($usersAttributes[$key], $value))
                {
                    $error = $error.' Ligne '.($i+1).', '.$key.' : "'.$value.'"  est invalide | ';
                }
                else
                {
                    $check = true;
                    if($key == 'email')
                    {
                        for($k = 0 ; $k < count($nicknameAndEmailsBDD) ; $k++ )
                        {
                            if($nicknameAndEmailsBDD[$k]['email'] == $value)
                            {
                                $check = false;
                                $error = $error.' Ligne '.($i+1).', '.$key.' : "'.$value.'" est déjà utilisé | ';
                            }
                        }

                    }
                    if($key == 'nickname')
                    {
                        for($k = 0 ; $k < count($nicknameAndEmailsBDD) ; $k++ )
                        {
                            if($nicknameAndEmailsBDD[$k]['nickname'] == $value)
                            {
                                $check = false;
                                $error = $error.' Ligne '.($i+1).', '.$key.' : "'.$value.'" est déjà utilisé | ';
                            }
                        }
                    }
                    if($key == 'campus')
                    {
                        $exist = false;
                        for($k = 0 ; $k < count($campusBDD) ; $k++ )
                        {
                            if($campusBDD[$k]->getName() == $value)
                            {
                                $exist = true;
                            }
                        }
                        if(!$exist) {
                            $error = $error.' Ligne '.($i+1).', '.$key.' : "'.$value.'" n\'existe pas | ';
                            $check = false;
                        }

                    }

                    if($check)
                    {
                        $counter1++;
                    }
                }
            }
            if($counter1 == count($data[$i]))
            {
                $globalCounter++;
            };
        }

        $error=substr_replace($error, '. ', strlen($error)-3, 3 );

        if($globalCounter == count($data)) return null;
        else return $error;
    }

    private function addUsers($data,EntityManagerInterface $entityManager, CampusRepository $campusRepository, UserPasswordHasherInterface $passwordHasher, RoleRepository $roleRepository )
    {
        $campusBDD = $campusRepository->findAll();
        for($i = 0 ; $i<count($data) ; $i++)
        {
            $user = new User();
            $user
                ->setNickname($data[$i]['nickname'])
                ->setName($data[$i]['name'])
                ->setSurname($data[$i]['surname'])
                ->setPhoneNumber($data[$i]['phoneNumber'])
                ->setEmail($data[$i]['email']);

                $userRole = $roleRepository->findOneBy(['role' => 'ROLE_USER']);
                $user->addRole($userRole);

                $hashedPassword = $passwordHasher->hashPassword($user, $data[$i]['password']);
                $user->setPassword($hashedPassword);


            for($k = 0 ; $k < count($campusBDD) ; $k++ )
            {
                if($campusBDD[$k]->getName() == $data[$i]['campus'])
                {
                    $user->setCampus($campusBDD[$k]);
                }
            }
            $entityManager->persist($user);
            $entityManager->flush();
        }
    }


}
