<?php

namespace App\Form;

use App\Entity\RecipeIngredient;
use App\Entity\Ingredient; // Assure-toi que cette ligne est présente
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeIngredientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ingredient', EntityType::class, [
                'class' => Ingredient::class,
                'choice_label' => 'name',
                'label' => 'Ingrédient',
                'attr' => [
                    'class' => 'form-select ingredient-select',
                ],
            ])
            ->add('quantity', TextType::class, [
                'label' => 'Quantité',
                'attr' => [
                    'class' => 'form-control quantity-input',
                    'placeholder' => 'Entrez la quantité',   
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RecipeIngredient::class,
        ]);
    }
}
