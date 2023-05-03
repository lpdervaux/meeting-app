<?php

namespace App\Form;

use App\Entity\Campus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nickname', TextType::class, [
                'label' => 'Pseudo',
            ])
            ->add('surname', TextType::class, [
                'label' => 'Prénom'
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom'
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Téléphone'
            ])
            ->add('email', TextType::class, [
                'label' => 'Email'
            ])
            ->add('plainPassword', RepeatedType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'type' => PasswordType::class,
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'groups' => ['new'],
                        'message' => 'Le mot de passe ne peut pas être vide',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractère',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
                'first_options' => ['label' => 'Mot de passe', 'required' => false],
                'second_options' => ['label' => 'Confirmation','required' => false]
            ])
            ->add('campus', EntityType::class, [
                'label' => 'Campus',
                'class' => Campus::class,
                'choice_label' => 'name',
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Télécharger vers le server',
                'required' => false,
                'download_uri' => false,
                'delete_label' => 'Cochez si vous souhaitez supprimer votre image de profil',
                'constraints' => [
                    new Assert\Image([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'maxSizeMessage' => 'Le fichier ne doit pas dépasser {{ limit }}.',
                        'mimeTypesMessage' => 'Le fichier doit être un fichier de type {{ types }}.',
                    ]),
                ],
            ])
        ;
    }
}
