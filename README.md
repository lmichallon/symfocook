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
On installe le Bundle "EasyAdminBundle", qui va nous permettre de générer des CRUD pour les recettes et les utilisateurs..
Pour cela, on l'installe via Composer avec la commande ``composer require easycorp/easyadmin-bundle``.

On exécute ensuite la commande ``php bin/console make:admin:dashboard`` afin de générer un controlleur d'administration 
(que l'on retrouve dans *'src/Controller/Admin/DashboardBackOfficeController*).

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
⚠️ On choisit de laisser l'url par défaut pour la route permettant d'accéder au Back-Office, à savoir "/admin".
Ainsi, dans "*config/packages/security.yaml*", on ajoute la ligne suivante :
```yaml
security:
    access_control:
        - { path: ^/admin, roles: ROLE_ADMIN }
```
qui permet de limiter l'accès à toutes les pages du Back-office (dont l'url commencera donc par "/admin") uniquement aux utilisateurs dont le rôle est "ROLE_ADMIN".

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

    yield MenuItem::section('Utilisateurs');
    yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-user', User::class);

    yield MenuItem::section('Recettes');
    yield MenuItem::linkToCrud('Recettes', 'fa fa-utensils', Recipe::class);
}
```

On peut ensuite générer un contrôleur pour le CRUD de l'entité 'Recipe', avec la commande ``php bin/console make:admin:crud``.
On fait de même pour le CRUD de l'entité 'User'.

Il faut alors modifier les fonctions "configureFields" de ces contrôleurs pour que les champs correspondent à ceux stockés en base de données.
*Exemple pour 'Recipe'* : 
```php 
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
```

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