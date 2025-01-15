<?php

namespace App\Form;

use App\Entity\Ingredient;
use App\Entity\RecipeIngredient;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class RecipeIngredientType extends AbstractType
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ingredient', EntityType::class, [
                'class' => Ingredient::class,
                'choice_label' => 'name',
                'label' => 'Ingrédient',
                'placeholder' => 'Choisissez un ingrédient',
                'attr' => [
                    'class' => 'form-select ingredient-select',
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('i')
                        ->orderBy('i.name', 'ASC');             // Sorting in alphabetical order
                },
                'required' => true,
                'constraints' => [
                    new NotNull(['message' => 'Vous devez sélectionner un ingrédient.']),
                ],
            ])
            ->add('quantity', TextType::class, [
                'label' => 'Quantité',
                'required' => true,
                'attr' => [
                    'class' => 'form-control quantity-input',
                    'placeholder' => 'Entrez la quantité',
                ],
                'constraints' => [
                    new NotNull(['message' => 'Vous devez sélectionner une quantité.']),
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
