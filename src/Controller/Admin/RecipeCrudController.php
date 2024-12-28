<?php

namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Enum\Difficulty;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class RecipeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Recipe::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),        // "hideOnForm"--> permet de masquer ce champ sur la visualisation des recettes
            TextField::new('title', 'Titre'),
            TextareaField::new('content', 'Contenu'),
            NumberField::new('duration', 'Durée (en minutes)'),
            ChoiceField::new('difficulty', 'Difficulté')
                ->setChoices([
                    'Facile' => Difficulty::EASY,
                    'Moyen' => Difficulty::MEDIUM,
                    'Diffcile' => Difficulty::HARD,
                ]),
            AssociationField::new('author', 'Auteur'),
            AssociationField::new('category', 'Catégorie'),
        ];
    }
}
