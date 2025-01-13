<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;


class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse Email',
                'attr' => [
                    'class' => 'form-control recipe-input',
                    'placeholder' => 'Entrez votre email',
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Mot de Passe',
                    'attr' => [
                        'class' => 'form-control recipe-input',
                        'placeholder' => 'Entrez un mot de passe',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le Mot de Passe',
                    'attr' => [
                        'class' => 'form-control recipe-input',
                        'placeholder' => 'Confirmez le mot de passe',
                    ],
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Créer un compte',
                'attr' => [
                    'class' => 'btn btn-success recipe-submit',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
