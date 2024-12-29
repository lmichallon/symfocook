<?php

namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Entity\User;
use App\Enum\Difficulty;
use Doctrine\ORM\EntityManagerInterface;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bundle\SecurityBundle\Security;

class RecipeCrudController extends AbstractCrudController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public static function getEntityFqcn(): string
    {
        return Recipe::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnIndex()        // "hideOnIndex" --> allows to hide this field on listing arrays
                ->hideOnForm(),        // "hideOnForm"--> allows to hide this field on forms
            TextField::new('title', 'Titre'),
            TextareaField::new('content', 'Déroulé de la recette')->setSortable(false),    // "setSortable(false)"--> allows to prevent sorting
            NumberField::new('duration', 'Durée (en minutes)'),

            // Difficulty field only for forms
            ChoiceField::new('difficulty', 'Difficulté')
                ->setChoices([
                    'Facile' => Difficulty::EASY,
                    'Moyen' => Difficulty::MEDIUM,
                    'Difficile' => Difficulty::HARD,
                ])
                ->onlyOnForms(),

            // Difficulty field only for tables
            ChoiceField::new('difficulty', 'Difficulté')
                ->setChoices([
                    'Facile' => Difficulty::EASY->value,
                    'Moyen' => Difficulty::MEDIUM->value,
                    'Difficile' => Difficulty::HARD->value,
                ])
                ->onlyOnIndex(),

            AssociationField::new('author', 'Auteur')->hideOnForm(),
            AssociationField::new('category', 'Catégorie'),
        ];
    }

    
    // Allows to display textual values instead of id for foreign keys' fields
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


    // Allows to set by automatically the connected user as new recipe's author
    public function persistEntity(EntityManagerInterface $entityManager, $entity): void
    {
        // Inserting the connected admin user as author of new recipe
        if ($entity instanceof Recipe) {
            $connectedUser = $this->security->getUser();

            if ($connectedUser) {
                $entity->setAuthor($connectedUser);
            } else {
                throw new \RuntimeException('Impossible de reconnaître l\'utilisateur connecté !');
            }
        }

        // For testing with a predefined user (the one with ID number 1)
//        if ($entity instanceof Recipe) {
//            $defaultAuthor = $entityManager->getRepository(User::class)->find(1);
//
//            if ($defaultAuthor) {
//                $entity->setAuthor($defaultAuthor);
//            } else {
//                throw new \RuntimeException('Utilisateur de test introuvable.');
//            }
//        }

        parent::persistEntity($entityManager, $entity);
    }
}
