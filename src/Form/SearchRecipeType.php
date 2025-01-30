<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Ingredient;
use App\DTO\SearchOptions;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchRecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'placeholder' => 'Toutes les catégories',
                'choice_label' => 'name',
                'choice_value' => 'name',
            ])
            ->add('ingredient', EntityType::class, [
                'class' => Ingredient::class,
                'placeholder' => 'Tous les ingrédients',
                'choice_label' => 'name',
                'choice_value' => 'name',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchOptions::class,
        ]);
    }
}
