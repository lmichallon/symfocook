# Symfocook

_Projet Symfony dans le cadre du Mastère 1 en Ingénierie du Web à l'ESGI de Lyon_

_Par Michalon Lisa, Duperthuy Hugo et Cauvet Louis_

## Objectifs du projet

Notre objectif est de développer en Symphony un petit site de recettes, avec un système d'inscription/connexion d'utilisateurs.
Par défaut, le site doit proposer une liste de recettes variées, qu'on doit pouvoir filtrer par catégorie, diffficulté ou en cumulant les 2.

On doit aussi pouvoir effectuer une recherche, afin d'obtenir les recettes dont le nom, la description ou les ingrédients correspondent à ce qui a été saisi.
Chaque recette se composera d'un ou plusieurs ingrédients, parmi une liste prédéfinie en base de données (ce qui fait qu'on aura une relation *manyToMany* entre ingrédients et recettes.

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

    $this->hashPassword($entity);

    $event = new SendMailEvent($entity);
    $this->dispatcher->dispatch($event, SendMailEvent::NAME);
}
```
⚠️ La fonction ```prePersist()``` se déclanche avant que les données du nouvel utilisateur ne soient stockées en base de données,car on l'a spécifié 
dans *"config/services.yaml"*:
```yaml
App\EventListener\UserPasswordListener:
    tags:
        - { name: 'doctrine.event_listener', event: 'prePersist', method: 'prePersist' }
```

- J'ai également mis en place un event listener personalisé qui permet d'envoyer un mail de confirmation de création de son compte à l'utilisateur, dans *"src/EventListener/SendMailListener.php"*:
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
Cet event listener se déclanche une fois un utilisateur enregistré, car on l'a défini ainsi dans *"config/services.yaml"* :
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
