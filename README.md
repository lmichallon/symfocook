# Symfocook 
*Projet Symfony dans le cadre du Mastère 1 en Ingénierie du Web à l'ESGI de Lyon*

*Par Michalon Lisa, Duperthuy Hugo et Cauvet Louis*


## Objectif du projet
Notre objectif est de développer en Symphony un petit site de recettes, avec un système d'inscription/connexion d'utilisateurs.
Par défaut, le site doit proposer une liste de recettes variées, qu'on doit pouvoir filtrer par catégorie ou diffficulté.
On doit aussi pouvoir effectuer une recherche, afin d'obtenir les recettes dont le nom, la description ou les ingrédients correspondent à ce qui a été saisi.

De plus, un utilisateur connecté pourra créer une nouvelle recette sur la site, modifier les recettes qu'il a créé mais aussi ajouter des recettes dans ces favoris.

Afin, un utilisateur possédant le rôle admin bénéficiera d'un Back-Office depuis lequel il pourra modifier, ajouter ou supprimer toutes les recettes et les autres utilisateurs.


## Réalisation du projet

### 1) Création des fixtures *(par Louis Cauvet)*
Afin d'obtenir suffisament de données de tests, j'ai demandé à ChatGpt de me générer des fichiers JSON recensant une dizaine de catégories de recettes, 
240 ingrédients, 90 recettes et toutes les relations ingrédient-recette nécéssaires pour obtenir des données réalistes.

Une fois un résultat satisfaisant obtenu, et la bonne corrrepondance entre les données vérifiée, j'ai mis en place dans 
*"src/DataFixtures/AppFixtures.php"* plusieurs fonctions permettant de d'insérer ces données dans la base de données à partir des fichiers JSON.

### 2) Mise en place du Back-Office pour les utilisateurs admins *(par Louis Cauvet)*
#### Ajout du bundle EasyAdmin au projet
On installe le Bundle "EasyAdminBundle", qui va nous permettre de générer des CRUD pour les recettes et les utilisateurs..
Pour cela, on l'installe via Composer avec la commande ``composer require easycorp/easyadmin-bundle``.

On exécute ensuite la commande ``php bin/console make:admin:dashboard`` afin de générer un controlleur d'administration 
(que l'on retrouve dans *'src/Controller/Admin/DashboardBackOfficeController*).

#### Configuration des routes du Back-Office
On active ensuite un chargeur de route personnalisé dans notre application, en créant le fichier *'config/routes/easyadmin.yaml'*
et en y insérant le code :
```yaml
easyadmin:
    resource: .
    type: easyadmin.routes
```
⚠️Ce code permet d'indiquer à Symfony qu'il doit déléguer la gestion des routes au bundle EasyAdmin, puisque Symfony ne 
connaît pas nativement les routes que génère le bundle. Ainis, lorsqu'on ajoutera des contrôleurs CRUD, ce charger de routes 
créera de lui-même toutes les urls nécéssaires pour chacun d'entre eux.
(source : https://symfony.com/bundles/EasyAdminBundle/current/dashboards.html#pretty-admin-urls)

On indique ensuite que lors de l'arrivée d'un admin dans le back-office, il doit être redirigé sur la page de gestion des recettes :
```php
#[Route('/admin', name: 'admin')]
    public function index(): Response
    {
         $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
         return $this->redirect($adminUrlGenerator->setController(RecipeCrudController::class)->generateUrl());
    }
```

#### Ajout des restrictions d'accès au BO
⚠️ On choisit de laisser l'url par défaut pour la route permettant d'accéder au Back-Office, à savoir "/admin".
Ainsi, dans "*config/packages/security.yaml*", on ajoute la ligne suivante :
```yaml
security:
    access_control:
        - { path: ^/admin, roles: ROLE_ADMIN }
```
qui permet de limiter l'accès à toutes les pages du Back-office (dont l'url commencera donc par "/admin") uniquement aux utilisateurs dont le rôle est "ROLE_ADMIN".

#### Paramétrage de l'interface du BO
On ajoute ensuite la configuration de notre back-office dans les fonctions du controlleur, conformément aux différentes options
qui nous sont présentées dans la doc du Bundle :
```php
public function configureDashboard(): Dashboard
{
    return Dashboard::new()
        ->setTitle('SymfoCook - Back Office');
}

public function configureMenuItems(): iterable
{
    yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

    yield MenuItem::section('Gestion des utilisateurs');
    yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-user', User::class);

    yield MenuItem::section('Gestion des recettes');
    yield MenuItem::linkToCrud('Recettes', 'fa fa-utensils', Recipe::class);
}
```

#### Création des CRUD pour les recettes et les utilisateurs
On peut ensuite générer un contrôleur pour le CRUD de l'entité 'Recipe', avec la commande ``php bin/console make:admin:crud``.
On fait de même pour le CRUD de l'entité 'User'.

Il faut alors modifier les fonctions "configureFields" de ces contrôleurs pour que les champs correspondent à ceux stockés en base de données.
*Exemple pour 'Recipe'* : 
```php 
public function configureFields(string $pageName): iterable 
{
    return [
        IdField::new('id')
            ->hideOnIndex()        // "hideOnIndex" --> allows to hide this field on listing arrays
            ->hideOnForm(),        // "hideOnForm"--> allows to hide this field on forms        
        TextField::new('title', 'Titre'),
        TextareaField::new('content', 'Déroulé de la recette')->setSortable(false),      // "setSortable(false)"--> allows to prevent sorting
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
```

#### Gestion de la déconnexion au BO
On souhaite ajouter un lien de déconnexion dans le menu du Back-Office. Pour cela, on ajoute la ligne suivante :
```php
yield MenuItem::linkToLogout('Déconnexion', 'fa fa-door-open');
```
dans la fonction "configureMenuItems" du controller de Dashboard.
On doit alors préciser le point de déconnexion dans *'config/packages/security.yaml'* :
```yaml
security:
    firewalls:
        main:
            pattern: ^/
            logout:
                path: app_logout
                target: /
```
et renseigner la route correspondante dans le controleur du Dashboard :
```php
#[Route('/logout', name: 'app_logout')]
public function logout(): void
{
}
```
⚠️ Cette fonction peut rester vide, car le code ajouté dans *'security.yaml'* effectuera une redirection automatique 
vers la page d'acueil du site (grâce à l'attribut 'target').

#### Modification de l'affichage des clés étrangères dans les tableaux du BO
On souhaite également que sur les tableaux de listing des différentes recettes, ce ne soit pas l'dientifiant de l'auteur 
mais son adresse mail qui soit affichée. Pour cela, on peut ajouter la méthode magique ``__toString`` dans l'entité "User" :
```php
public function __toString()
{
    return $this->email;
}
```

Cependant, le tri de la colone s'effetue toujours selon l'ordre des identifiants. Pour résoudre ce problème, on a besoin d'une 
fonction qui convertisse la demande de tri sur la colonne 'Auteur' (représentée par ``$searchDto->getSort()['author']``)
en la requête SQL :
```sql
SELECT entity FROM Recipe entity
LEFT JOIN entity.author author
ORDER BY author.email ASC
```
Ainsi, la fonction permettantd e réaliser ceci (qui est à ajouter dans le controlleur du CRUD de recettes) est la suivante : 
```php
 public function createIndexQueryBuilder(SearchDto $searchDto,EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
{
    $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

    if ($searchDto->getSort() && isset($searchDto->getSort()['author'])) {
        $qb->orderBy('author.email', $searchDto->getSort()['author']);
    }

    return $qb;
}
```

#### Traduction des champs natifs de EasyAdmin
Actuellement, les champs liés aux fonctionnalités natives de EasyAdmin s'affichent en anglais ("Add", "Edit", "Delete", "Search", Previous", "Next", "Results"...).
On souhaite donc paramétrer la langue du bundle en français pour que ces champs soient traduits.
Pour cela, il faut modifier la langue par défaut du projet, dans *"config/packages/translation.yaml*:
```yaml
framework:
    default_locale: fr
```
⚠️ On peut laisser "en" pour la partie "fallbacks" de ce fichier, afin que les chaînes dont la traduction française n'est pas trouvée s'affichent en anglais par défaut.

Cependant, les noms des entités sont toujours en anglais, puisqu'elles ont été définies ainsi dans le code ("Recipe", "User").
<span style="color: #FF0000">A effectuer !</span>


#### Rectification/Personnalisation du formulaire de création de recette
Lorsqu'on essaie de créer une nouvelle recette, on rencontre une erreur car le champ "Difficulté" du formulaire se base sur 
une valeur textuelle au lieu d'une valeur que l'on retrouve dans l'énumération "Difficulty".
Pour corriger ceci tout en continuant d'avoir une valeur textuelle affichée dans les tableaux de listing de recettes, il nous faut dissocier les 2 cas dans la définition des champs
du contrôleur du CRUD de recettes, en remplaçant ceci :
```php
ChoiceField::new('difficulty', 'Difficulté')
->setChoices([
    'Facile' => Difficulty::EASY,
    'Moyen' => Difficulty::MEDIUM,
    'Difficile' => Difficulty::HARD,
]),
```
par ceci :
```php
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
```

Autre souci, lors de la création d'une recette via le formulaire, il y a aussi par défaut un champ "Auteur" permettant d'indiquer
quel utilisateur est l'auteur de cette recette, alors qu'on souhaite que la recette fraîchement créée soit automatiquement associée 
à l'admin connecté. Pour ceci, on va masquer le champ "Auteur" des formulaires, dans la fonction "configureFields" du contrôleur de CRUD de recettes :
```php
AssociationField::new('author', 'Auteur')->hideOnForm(),
```
Il nous faut aussi définir une surcharge de la fonction 'persistEntity' du contrôleur, afin de capter l'utilisateur connecté et de le définir 
comme étant l'auteur de la recette dans la base de données.
Pour cela :
```php
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

        parent::persistEntity($entityManager, $entity);
    }
```
*(Source : https://symfony.com/bundles/EasyAdminBundle/current/crud.html#creating-persisting-and-deleting-entities)*

⚠️On a besoin d'injecter la dépendance "Security" dans le constructeur de la classe, 
car c'est elle qui nous permet de récupérer ensuite l'utilisateur connecté (via la variable ``$security``) :
```php
private Security $security;

public function __construct(Security $security)
{
    $this->security = $security;
}
```

#### Ajout de la liste des ingrédients pour chaque recette
Dans notre tableau de listing des recettes, il nous manque également le détail des ingrédients et des quantités nécéssaires 
pour chaque recette, puisque ces données proviennent d'autres tables qui 'RecipeIngredient' (pour la quantité de chaque ingrédient) et 'Ingredient' (pour le nom des ingrédients).   
Pour le rajouter au tableau, il faut donc définir un nouveau champ dans la fonction 'configureFields' du contrôleur de CRUD de recettes, à savoir :
```php
CollectionField::new('ingredients', 'Détail des ingrédients')
    ->formatValue(function ($value,$entity) {
        return implode(', ', $entity->getIngredients()->map(function ($recipeIngredient) {
            return $recipeIngredient->getIngredient()->getName() . ' (' . $recipeIngredient->getQuantity() . ')';
        })->toArray());
    })
    ->onlyOnIndex(),
```
⚠️Ces lignes permettent de récupérer les données que l'on désire grâce aux fonctions ``getIngredients`` de l'entité 'Recipe', et 
``getIngredient`` de l'entité 'RecipeIngrédient', qui font un travail équivalent à des jointures SQL pour récupérer les données d'autres entités/tables
vers celle dans laquelle on se situe (à savoir "Recipe").  Elle permet également de définir le format d'affichage des données dans la cellule de tableau.


#### Inclusion de la liste des ingrédients dans les formulaires de création/de modification de recette (partie assistée par l'IA)
Pour pouvoir indiquer la liste des ingrédients lors de la création ou de la modification d'une recette, il faut que l'on ajoute de nouveaux champs aux formulaires.
Cependant, la relation complexe OneToMany établie entre "Recipe" et "RecipeIngredient" implique de devoir mettre en place un formulaire personnalisé,
avec un répéteur permettant de saisir autant d'ingérdients que nécéssaire. Ainsi, on définit dans la fonction
"ConfigureField" du contrôleur du CRUD de recettes le champ personnalisé suivant :
```php
 CollectionField::new('ingredients', 'Détail des ingrédients')
    ->setEntryType(RecipeIngredientType::class)           // Personalized form
    ->onlyOnForms(),
```
qui correspond à une nouvelle classe "src/Form/RecipeIngredientType.php", dans laquelle on retourve la fonction suivante :
```php
public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
        ->add('ingredient', EntityType::class, [
            'class' => Ingredient::class,
            'choice_label' => 'name',
            'label' => 'Ingrédient',
            'placeholder' => 'Choisissez un ingrédient',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('i')
                    ->orderBy('i.name', 'ASC');           // Sorting in alphabetical order
            },
            'required' => true,
            'constraints' => [
                new NotNull(['message' => 'Vous devez sélectionner un ingrédient.']),
            ],
        ])
        ->add('quantity', TextType::class, [
            'label' => 'Quantité',
            'required' => true,
            'constraints' => [
                new NotNull(['message' => 'Vous devez sélectionner une quantité.']),
            ],
        ]);
}
```
⚠️Cette fonction permet de récupérer tous les ingrédients disponibles dans la base de données, et de les transformer en une 
liste de choix (triée par ordre alphabétique), accompagnée d'un champ texte qui indique la quantité associée à l'ingrédient choisi.
On a ajouté un contrainte qui oblige à renseigner une quantité si un ingrédient est sélectionné, et inversement.

<span style="color: #FF0000">Evolution possible :</span> Ce qui aurait été encore mieux, c'est que l'utilisateur se voit proposer
la liste de tous les ingrédients dans la base de données, mais qu'il saisisse librement du texte dans un champ afin que les propositions 
d'ingrédients se réduisent au fur et à mesure de la saisie (comme pour une datalist).   
Ca aurait notamment permis à un utilisateur de renseigner un nouvel ingrédient à ajouter à la base de données dans le cas
où aucun de la liste ne correspond à ses besoins.