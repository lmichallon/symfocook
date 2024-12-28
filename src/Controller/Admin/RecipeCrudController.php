<?php

namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Enum\Difficulty;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;;

class RecipeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Recipe::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnIndex()        // "hideOnIndex" --> permet de masquer ce champ sur les tableaux de listing
                ->hideOnForm(),        // "hideOnForm"--> permet de masquer ce champ sur les formulaires
            TextField::new('title', 'Titre'),
            TextareaField::new('content', 'Contenu')->setSortable(false),    // "setSortable(false)"--> permet d'empêcher le tri
            NumberField::new('duration', 'Durée (en minutes)'),
            ChoiceField::new('difficulty', 'Difficulté')
                ->setChoices([
                    'Facile' => Difficulty::EASY->value,
                    'Moyen' => Difficulty::MEDIUM->value,
                    'Difficile' => Difficulty::HARD->value,
                ]),
            AssociationField::new('author', 'Auteur'),
            AssociationField::new('category', 'Catégorie'),
        ];
    }

    public function createIndexQueryBuilder(SearchDto $searchDto,EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if ($searchDto->getSort() && isset($searchDto->getSort()['author'])) {
            $qb->orderBy('author.email', $searchDto->getSort()['author']);
        }

        if ($searchDto->getSort() && isset($searchDto->getSort()['category'])) {
            $qb->orderBy('category.name', $searchDto->getSort()['category']);
        }

        return $qb;
    }
}
