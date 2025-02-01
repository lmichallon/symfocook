<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ResetPasswordType extends AbstractType
{
    // Personalizing a form which allows to change user's password if he asked it
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', PasswordType::class, [
                'label' => 'Nouveau mot de passe',
                'attr' => [
                    'class' => 'form-control recipe-input',
                    'placeholder' => 'Entrez un nouveau mot de passe',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le mot de passe ne peut pas être vide.',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                    ]),
                    new Regex([
                        'pattern' => '/[A-Z]/',
                        'message' => 'Le mot de passe doit contenir au moins une lettre majuscule.',
                    ]),
                    new Regex([
                        'pattern' => '/[a-z]/',
                        'message' => 'Le mot de passe doit contenir au moins une lettre minuscule.',
                    ]),
                    new Regex([
                        'pattern' => '/\d/',
                        'message' => 'Le mot de passe doit contenir au moins un chiffre.',
                    ]),
                    new Regex([
                        'pattern' => '/[\W_]/',
                        'message' => 'Le mot de passe doit contenir au moins un caractère spécial (par exemple : ! @ # $ % ^ & *).',
                    ]),
                ],
            ])
            ->add('confirm_password', PasswordType::class, [
                'label' => 'Confirmez le mot de passe',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control recipe-input',
                    'placeholder' => 'Confirmez le mot de passe',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La confirmation ne peut pas être vide.',
                    ]),
                ],
            ])
            ->add('_token', HiddenType::class, [
                'data' => $options['csrf_token_id'] ?? 'reset_password',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Réinitialiser le mot de passe',
                'attr' => [
                    'class' => 'btn btn-success recipe-submit',
                ],
            ]);


        // Checking if the form fields' values are identicals for valid the password
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();

            $password = $form->get('password')->getData();
            $confirmPassword = $form->get('confirm_password')->getData();

            if ($password !== $confirmPassword) {
                $form->get('confirm_password')->addError(new FormError('Les mots de passe ne correspondent pas.'));
            }
        });
    }
}