# Symfocook

_Projet Symfony dans le cadre du Mastère 1 en Ingénierie du Web à l'ESGI de Lyon_

_Par Michallon Lisa (lmichallon), Duperthuy Hugo (HeavenProx) et Cauvet Louis (Louis-Cauvet)_

## Objectifs du projet

Notre objectif est de développer en Symphony un petit site de recettes, avec un système d'inscription/connexion d'utilisateurs.
Par défaut, le site doit proposer une liste de recettes variées, qu'on doit pouvoir filtrer par catégorie, diffficulté ou en cumulant les 2.

On doit aussi pouvoir effectuer une recherche, afin d'obtenir les recettes dont le nom, la description ou les ingrédients correspondent à ce qui a été saisi.
Chaque recette se composera d'un ou plusieurs ingrédients, parmi une liste prédéfinie en base de données (ce qui fait qu'on aura une relation *manyToMany* entre ingrédients et recettes).

De plus, un utilisateur connecté pourra créer une nouvelle recette sur la site, et modifier les recettes qu'il a créé.

Enfin, un utilisateur possédant le rôle admin bénéficiera d'un Back-Office depuis lequel il pourra modifier, ajouter ou supprimer toutes les recettes et les autres utilisateurs.
Il pourra également demander la réinitialisation du mot de passe d'un autre utilisateur.

## Données de tests
A l'aide des fixtures mises en place, nous avon pu définir des données de tests réalistes, notamment 2 comptes utilisateurs :
- 1 compte admin : login = **admin@symfocook.com** et mdp = **adminPass** 
- 1 Compte 'utilisateur lambda' : login = **user@test.com** et mdp : **testPass**

## Points techniques du projet

### - Création des fixtures _(par Louis Cauvet)_

Afin d'obtenir suffisament de données de tests, j'ai demandé à ChatGpt de me générer des fichiers JSON recensant une dizaine de catégories de recettes,
240 ingrédients, 90 recettes et toutes les relations "ingrédient-recette nécéssaires" pour obtenir des données réalistes.

Une fois un résultat satisfaisant obtenu, et la bonne corrrepondance entre les données vérifiée, j'ai mis en place dans
_"src/DataFixtures/AppFixtures.php"_ plusieurs fonctions permettant d'insérer ces données dans la base de données à partir des fichiers JSON.

### - Modification de l'affichage des clés étrangères dans les tableaux du BO _(par Louis Cauvet)_
Dans le BO créé à l'aide du bundle EasyAdmin, on retrouve des tableaux listant les différentes recettes de la base de données.
Cependant, dans la colonne 'Auteur' de ce tableau, les données qui apparaissent sont les identifiants numériques des utilisateurs, 
alors qu'on souhaiterai afficher leurs adresses mails pour + de clarté. 

Pour cela, j'ai ajouté la fameuse méthode magique `__toString` dans le code de l'entité "User" :
```php
public function __toString()
{
    return $this->email;
}
```
⚠️ Cette méthode permet d'afficher une certaine propriété de l'entité comme contenu textuel principal définissant l'entité (ici l'adresse mail).


Cependant, le tri de la colonne du tableau s'effectue toujours selon l'ordre des identifiants, et non des adresses mails. 

Pour résoudre ce problème, j'ai mis en place une fonction qui convertit la demande de tri sur la colonne 'Auteur' (représentée
dans le code par `$searchDto->getSort()['author']`) en la requête SQL suivante :
```sql
SELECT entity FROM Recipe entity
LEFT JOIN entity.author author
ORDER BY author.email ASC
```

Cette fonction est la suivante (que j'ai ajoutée dans le controlleur du CRUD de recettes *"src/Controller/Admin/RecipeCrudController.php"*) :

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

### - Ajout d'ingrédients lors de la gestion d'une recette depuis le BO _(par Louis Cauvet)_
Pour gérer la relation 'ManyToMany' établie entre les entités 'Recette' et 'Ingrédient', j'ai du utiliser un formulaire personnalisé
qui est utilisé comme partie du formulaire permettant de créer ou de modifier des recettes depuis le Back-Office.
Ainsi, dans la fonction ```configureFields()``` de la classe *"src/Controller/Admin/RecipeCrudController.php"*, j'ai ajouté le champ suivant :
```php 
CollectionField::new('ingredients', 'Détail des ingrédients')
    ->setEntryType(RecipeIngredientType::class)     // Personalized form
    ->onlyOnForms(),
```
qui fait référence à la classe de formulaire que j'ai créée dans *"src/Form/RecipeIngredientType.php"*,
qui sert à définir le formulaire suivant :
```php 
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
```
Ce formulaire ```RecipeIngredientType``` me permet donc de choisir 1 ou plusieurs ingrédients et leurs quantités associées à la recette 
que l'admin créée/modifie.

<span style="color: #FF0000">Evolution possible :</span> Actuellement, la liste de tous les ingrédients est un simple champ ```select```
qui répertorie tous les ingrédients stockés en base de données, triés par ordre alphabétique.
Ce qui aurait été encore mieux, ça aurait été d'avoir un champ textuel qui permette à l'utilisateur de renseigner un aliment
qui n'existerait pas en base de données, mais tout en ayant une ```datalist``` qui propose des ingrédients de la base de données
qui correspondent à la saisie de l'utilisateur pour éviter d'éventuels doublons d'ingrédients.

### - Mise en place d'events listeners s'appliquant lors de la création d'un compte-utilisateur _(par Louis Cauvet)_
J'ai mis en place des events listeners qui se déclanchent lorsqu'un nouvel utilisateur est créé :

- Le premier permet de hasher automatiquement le mot de passe choisi par l'utilisateur. Pour cela, dans *"src/EventListener/UserPasswordListener.php"*,
j'ai défini les fonctions suivantes :
```php
private function hashPassword(User $user): void
{
    if (!$user->getPassword()) {
        return;
    }

    $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPassword());
    $user->setPassword($hashedPassword);
}

public function prePersist(LifecycleEventArgs $args): void
{
    $entity = $args->getObject();

    if (!$entity instanceof User) {
        return;
    }

```php
 public function onUserRegistered(SendMailEvent $event): void
{
    $user = $event->getUser();

    $email = (new Email())
        ->from('no-reply@symfocook.com')
        ->to($user->getEmail())
        ->subject('Bienvenue chez Symfocook')
        ->html("<p>Bonjour,</p>
            <p>Nous avons le plaisir de vous confirmer la création de votre compte Symfocook !</p>
            <br>
            <p>Au plaisir de vous retrouver parmi nous.</p>
            <p>L'équipe Symfocook</p>");

    $this->mailer->send($email);
}
```
Cet event listener se déclenche une fois un utilisateur enregistré, car on l'a défini ainsi dans *"config/services.yaml"* :
```yaml
App\EventListener\SendMailListener:
    tags:
        - { name: 'kernel.event_listener', event: 'user.registered', method: 'onUserRegistered' }
```

### - Mise en place de la procédure d'envoi de mail de réinitialisation de mot de passe _(par Louis Cauvet)_
Je souhaitais qu'un admin ait la possibililité de demander la réinitialisation du mot de passe d'un utlisateur depuis le Back-Office.
Pour cela, j'ai utilisé la fonction ```configureActions()``` de EasyAdmin dans *"src/Controller/Admin/UserCrudController.php"*, afin de rajouter une action pour chacune des pages de modification d'un utilisateur:
```php
 public function configureActions(Actions $actions): Actions
{
    $resetPassword = Action::new('resetPassword', 'Réinitialiser le mot de passe')
        ->linkToCrudAction('sendPasswordResetEmail')
        ->setCssClass('btn btn-primary');
}
```
Lorsque l'admin clique sur ce CTA, cela appelle la fonction ```sendPasswordResetEmail``` de *"src/Controller/Admin/UserCrudController.php"* :
```php
public function sendPasswordResetEmail(AdminContext $context): Response
{
    // Getting the user concerned by the password reset request
    $user =$this->getUserFromContext($context);

    if (!$user) {
        return $this->redirectWithMessage('L\'utilisateur concerné par la demande de réinitialisation de mot de passe est introuvable.', false);
    }

    // Generating and saving user's reset token
    $resetToken = $this->generateAndSaveResetToken($user);

    // Sending the reset email to user
    $this->sendResetEmail($user, $resetToken);

    // Redirecting with success message
    return $this->redirectWithMessage('Email de réinitialisation envoyé avec succès.', true);
}
```
⚠️ Cette fonction est découpée en plusieurs fonctions, qui vont notamment permettre de récupérer l'utilisateur concerné par la demande de réinitialisation,
générer un token et une date d'expiration de ce token qui seront stockés en base de données, puis d'envoyer un mail à l'utlisateur, qui contient le lien vers le 
formulaire de réinitialisation de son mot de passe. 
Lorsque l'utilisateur en question cliquera sur ce lien, cela appelera le contrôlleur définit dans *"src/Controller/Admin/ResetUserPasswordController.php"* :
```php
#[Route('/reset-password/{token}', name: 'app_reset_password')]
public function resetPassword(
    string $token,
    Request $request,
    EntityManagerInterface $entityManager,

): Response {
    // Getting user based on his reset token
    $user = $entityManager->getRepository(User::class)->findOneBy(['resetPasswordToken' => $token]);

    // Handling any errors that may occur
    if (!$user || $user->getResetPasswordExpiresAt() < new \DateTimeImmutable()) {
        $this->addFlash('danger', 'Le lien de réinitialisation est invalide ou expiré.');
        return $this->redirectToRoute('connexion');
    }

    // Creating a custom form
    $form = $this->createForm(ResetPasswordType::class, null, [
        'csrf_token_id' => 'reset_password',
    ]);
    $form->handleRequest($request);

    // Checking and hashing the new password when the custom form is submitted
    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        $user->setPassword($data['password']);
        $user->setResetPasswordToken(null);
        $user->setResetPasswordExpiresAt(null);

        $entityManager->flush();

        $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');

        return $this->redirectToRoute('connexion');
    }

    // Calling the custom form's template
    return $this->render('reset_password/reset.html.twig', [
        'form' => $form->createView(),
    ]);
}
```
qui permet de vérifier que le token est encore valide, puis enregistrera le nouveau mot de passe choisi lors de la validation du formulaire 
qui se trouve sur le template 'reset_password/reset.html.twig' qu'il appelle.

### - Création du service de pagination (par _Lisa Michallon_)

L'idée était de m'inspirer du **KnpPaginatorBundle** que j'avais utilisé dans de précédents projets. Je me suis donc rendue sur le dépôt GitHub du bundle et parcouru les fichiers pour comprendre son fonctionnement... sans grand succès 😅

J'ai eu du mal à m'y retrouver et à comprendre ce que faisait chaque fichier et quelle était sa responsabilité. Après un moment, j'ai donc décidé de m'aider de l'IA pour y voir plus clair sur le découpage à adopter afin de respecter les principes **SOLID**, étant donné qu'ils ne me sont pas encore familliers. J'en ai compris que le KnpPaginatorBundle respectait le **Open/Closed Principle**, ce qui veut dire qu'il est ouvert à l'extension mais fermé à la modification. Il utilise des adapteurs pour gérer différentes sources de données et façon indépendante, sur lesquels s'appuie ensuite le Paginator pour manipuler les données tout en gardant une logique commune de pagination.

J'ai donc voulu reproduire ça dans mon découpage de fichiers :

- PaginatedResult : Encapsule les résultats paginés et leurs métadonnées. De cette façon, quel que soit le provider utilisé, la sortie du service sera toujours la même.

- Paginator : Le Paginator est le cœur du service de pagination. C'est le point d'entrée utilisé dans les Controllers et qui contient la logique métier. Il expose une méthode paginate, qui prend en paramètre un provider pour manipuler les données, une page courante et le nombre d'éléments par page. Il est utilisable dans n'importe quel contexte puisque découplé de la source des données grâve aux Providers.

- ProviderInterface : L'utilisation d'une interface ici permet de standardiser le comportement des providers en définissant un contrat minimal que chaque classe qui l'implémente devra respecter. De cette façon, toutes ses implémentations auront les mêmes méthodes principes, ce qui permettra une uniformité dans la logique de pagination.

- DoctrineProvider : Implémente les méthodes définies dans ProviderInterface, ici pour gérer les requêtes Doctrine via un QueryBuilder. Il a été nécessaire de cloner le QueryBuilder pour éviter les effets de bord à cause de la modification de l'objet original. Ce clone est utilisé pour obtenir le nombre total d'items ainsi que les items de la page active grâce à un calcul d'_offset_ en fonction de la page active et de nombre d'items à récupérer pour une page.

### Utilisation du bundle de modules js Webpack _(par Hugo Duperthuy)_

Dans notre projet, nous avons utilisé Webpack Encore, un bundle simplifiant l'utilisation de Webpack.
Son rôle principal est de compiler et minifier les fichiers CSS et JavaScript pour optimiser les performances de l'application.

Installation via Composer : `composer require symfony/webpack-encore-bundle`
Installation des dépendances avec npm : `npm install`

Nous avons ensuite réaliser le fichier de configuration pour Webpack Encore (webpack.config.js) :

```javascript
const Encore = require("@symfony/webpack-encore");

Encore.setOutputPath("public/build/")
  .setPublicPath("/build")
  .addEntry("app", "./assets/app.js")
  .enableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableBuildNotifications()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .enableSassLoader();

module.exports = Encore.getWebpackConfig();
```

Une fois la configuration en place, nous avons compilé les assets : `npm run dev`

Pour inclure les fichiers CSS et JavaScript générés par Webpack, nous les avons ajoutés dans le template de base (base.html.twig) afin qu’ils soient disponibles sur toutes les pages héritant de ce template :
La partie css dans le header et la partie js à la fin du body.

```html
{{ encore_entry_link_tags('app') }} {{ encore_entry_script_tags('app') }}
```

### Inscription et connexion d'un utilisateur _(par Hugo Duperthuy)_
#### Gestion de l'inscription 

L'inscription permet aux utilisateurs de créer un compte en fournissant une adresse e-mail et un mot de passe.
Un formulaire (UserType) est utilisé pour récupérer les informations de l'utilisateur.
Après soumission et validation du formulaire, le controller prend le relais avec la fonction inscription.

- une fois l'inscription réussie, un message flash est affiché et l'utilisateur est redirigé vers la page de connexion.
  Formulaire : src/Form/UserType.php | Controller : src/Controller/AuthController

#### Gestion de la connexion 

La connexion permet aux utilisateurs enregistrés de s'authentifier avec leur e-mail et mot de passe.
L'utilisateur saisit son adresse e-mail et son mot de passe dans le formulaire (LoginType)
Le controller avec sa fonction login vérifie si l'utilisateur existe en base de données et si le mot de passe est valide.

```php
$user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
if ($user && $passwordHasher->isPasswordValid($user, $password)) {
    return $this->redirectToRoute('home');
} else {
    $error = 'Identifiants invalides.';
}
```

En cas d'erreur, un message d'erreur est affiché.
Si les informations sont correctes, l'utilisateur est redirigé vers la page d'accueil.
Après connexion les boutons de la navbar ne sont plus inscription et connexion mais "mon compte" ou admin et deconnexion

```html
{% if is_granted('IS_AUTHENTICATED_FULLY') %}
<li class="nav-item">
  <span class="nav-link">Bonjour {{ app.user.email }}</span>
</li>
{% if is_granted('ROLE_ADMIN') %}
<li class="nav-item">
  <a class="nav-link" href="{{ path('admin') }}">Accès au BO</a>
</li>
{% else %}
<li class="nav-item">
  <a class="nav-link" href="{{ path('account') }}">Mon compte</a>
</li>
{% endif %}
<li class="nav-item">
  <a class="nav-link" href="{{ path('deconnexion') }}">Se déconnecter</a>
</li>
{% else %}
<li class="nav-item">
  <a class="nav-link" href="{{ path('inscription') }}">Inscription</a>
</li>
<li class="nav-item">
  <a class="nav-link" href="{{ path('connexion') }}">Connexion</a>
</li>
{% endif %}
```

Formulaire : src/Form/LoginType.php | Controller : src/Controller/AuthController

### Création de recette _(par Hugo Duperthuy)_

Les utilisateurs une fois connecté et depuis l'accueil, peuvent créer une recette en remplissant un formulaire.
Chaque recette peut contenir plusieurs ingrédients, qui sont gérés dynamiquement dans le formulaire.

Le contrôleur avec sa fonction ecrireRecette gère la création de recette et l'association des ingrédients.
Il suit cette logique :

- Création d'une nouvelle instance de Recipe et génération du formulaire à partir de RecipeType

```php
$recipe = new Recipe();
$form = $this->createForm(RecipeType::class, $recipe);
```

Ce formulaire contient plusieurs champs pour la recette.
Parmis ceux ci le champs imageFiles permettant l'upload des images :

```php
->add('imageFiles', FileType::class, [
    'label' => 'Télécharger des images',
    'multiple' => true,
    'mapped' => false,
    'required' => false,
])
```

Après avoir récupéré les images, on leur crée un nom unique et on les stocke dans un répertoire défini :

```php
foreach ($imageFiles as $imageFile) {
    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
    try {
        $imageFile->move(
            $this->getParameter('images_directory'),
            $newFilename
        );
    } catch (FileException $e) {
        $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
        return $this->redirectToRoute('new_recipe');
    }

    if ($newFilename) {
        $image = new RecipeImage();
        $image->setImagePath($newFilename);
        $recipe->addImage($image);
    }
}
```

Il faut définir le paramètre images_directory dans services.yaml :

```yaml
parameters:
  images_directory: "%kernel.project_dir%/public/uploads/images"
```

Si c'est bon on crée un objet RecipeImage et qu'on lie à la recette via une relation OneToMany
Et pour finir, on persist les images en bdd

Le formulaire contient également un champ de type CollectionType pour gérer la liste des ingrédients
CollectionType permet d'ajouter dynamiquement des ingrédients
"entry_type" est défini sur le formulaire RecipeIngredientType, qui est le formulaire qui gère les détails de chaque ingrédient.
"allow_add" est activé pour permettre l'ajout dynamique.

```php
->add('ingredients', CollectionType::class, [
    'entry_type' => RecipeIngredientType::class,
    'allow_add' => true,
```

Chaque ingrédient est représenté par une entité RecipeIngredient composé et est lié à Ingredient
Pour chaque ingrédient, il faut ainsi rentrer obligatoirement son nom et sa quantité.
On choisi l'ingrédient parmis une liste triée par ordre alphabétique.

```php
return $er->createQueryBuilder('i')
    ->orderBy('i.name', 'ASC');
```

Le formulaire utilise data-prototype pour gérer dynamiquement l'ajout d'ingrédients. Un script JavaScript récupère ce prototype et l'insère dans le DOM lorsque l'utilisateur clique sur "Ajouter un ingrédient" :

```html
<div
  id="ingredients"
  data-prototype="{{ form_widget(form.ingredients.vars.prototype)|e('html_attr') }}"
>
  <div class="row g-3" id="ingredients-container">
    {% for ingredientForm in form.ingredients %}
    <div class="col-3">
      <div class="ingredient-group border rounded p-3 shadow-sm">
        {{ form_row(ingredientForm.ingredient) }} {{
        form_row(ingredientForm.quantity) }}
      </div>
    </div>
    {% endfor %}
  </div>
</div>
```

- Après vérification de la soumission et validation du formulaire, il va associer l'auteur à la recette
- Persistance de la recette et de ses ingrédients en bdd
- Redirection après validation.

### Modification de recette _(par Hugo Duperthuy)_

Après le clique sur le bouton de modification de ma recette, on est redirigé sur le même formulaire que celui de création de recette, mais avec cette fois ci, nos données pré remplies :

- Le controller via sa fonction modify-recipe va récupérer la recette et ses données :

```php
$recipe = $entityManager->getRepository(Recipe::class)->find($id);
```

- Après vérification et qu'il s'agit du bon user, on crée le formulaire en lui injectant les données :

```php
$form = $this->createForm(RecipeType::class, $recipe);
$form->handleRequest($request);
```

- On gère ensuite les images
  (Problème ici : ma gestion n'a été faite que pour l'ajout d'une image et j'ai oublié de le changer)

```php
$imageFiles = $form->get('imageFiles')->getData();
if ($imageFiles) {
    $newFilename = uniqid() . '.' . $imageFiles->guessExtension();
    try {
        $imageFiles->move(
            $this->getParameter('images_directory'),
            $newFilename
        );
    } catch (FileException $e) {
        $this->addFlash('error', 'Une erreur s\'est produite lors de l\'upload de l\'image.');
    }

    // update image on entity
    $recipe->setImagePath($newFilename);
}
```

- On sauvegarde ensuite les modifications et le tour est joué :

```php
if ($form->isSubmitted() && $form->isValid()) {
    $entityManager->flush();

    $this->addFlash('success', 'La recette a été modifiée avec succès.');

    return $this->redirectToRoute('account');
}
```

Modification qui devraient être faites pour une meilleure modification de recette :

- Gestion d'ajout de plusieurs images comme mentionné plus tôt
- Possibilité de suppression des images déjà ajouté
- Possibilité d'ajout et suppresion des ingrédients :/

### Suppression de recette _(par Hugo Duperthuy)_

Si l'on clique sur le bouton de suppression de notre recette dans "Mon compte", la recette va pouvoir disparaitre de notre compte.
La fonction deleteRecipe dans notre controller va s'en charger :

- Vérification de l'user
- Récupération de la recette :

```php
$recipe = $entityManager->getRepository(Recipe::class)->find($id);
```

- Vérification que celui qui la supprime en est bien l'auteur :

```php
if ($recipe->getAuthor() !== $user) {
```

- Suppression de la recette et application sur la bdd :

```php
$entityManager->remove($recipe);
$entityManager->flush();
```

- Confirmation et redirection à mon compte où l'on peut voir que la recette n'y est plus