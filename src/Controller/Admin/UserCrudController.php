<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;
    private EntityManagerInterface $entityManager;
    private AdminUrlGenerator $adminUrlGenerator;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager,
        AdminUrlGenerator $adminUrlGenerator)
    {
        $this->passwordHasher = $passwordHasher;
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    // Allows to configure User fields in array lists & forms
    public function configureFields(string $pageName): iterable
    {
        // Defining structure for editing user's form
        if ($pageName === Crud::PAGE_EDIT) {
            return [
                IdField::new('id')->hideOnForm(),
                TextField::new('email', 'Adresse e-mail')
                    ->setFormTypeOption('disabled', true),         // Make the field unmodifiable

                // Display roles' choice in forms
                BooleanField::new('isAdmin', 'Utilisateur administrateur')
                    ->renderAsSwitch(false)
                    ->onlyOnForms(),
            ];
        }

        // Defining structure for adding user's form
        return [
            IdField::new('id')
                ->hideOnIndex()        // "hideOnIndex" --> allows to hide this field on listing arrays
                ->hideOnForm(),        // "hideOnForm"--> allows to hide this field on forms

            EmailField::new('email', 'Adresse e-mail')
                ->setHelp('Assurez-vous que cette adresse e-mail n\'est pas déja associée à un compte existant.'),

            // Display roles' list in array
            ArrayField::new('roles', 'Rôles')
                ->onlyOnIndex(),

            // Display roles' choice in forms
            BooleanField::new('isAdmin', 'Utilisateur administrateur')
                ->renderAsSwitch(false)
                ->onlyOnForms(),

            TextField::new('password', 'Mot de passe')
                ->hideOnIndex()
                ->setHelp('Le mot de passe doit contenir au moins 8 caractères, avec une majuscule, une minuscule, un chiffre et un caractère spécial.'),
        ];
    }

    // Allows to add a custom cta for reset user's password
    public function configureActions(Actions $actions): Actions
    {
        $resetPassword = Action::new('resetPassword', 'Réinitialiser le mot de passe')
            ->linkToCrudAction('sendPasswordResetEmail')
            ->setCssClass('btn btn-primary');

        return $actions->add(Crud::PAGE_EDIT, $resetPassword);
    }

    // Sending a mail to user with a link for reset his password
    public function sendPasswordResetEmail(AdminContext $context): Response
    {
        // Getting the user's id concerned by the password reset
        $entityId = $context->getRequest()->query->get('entityId');

        // Checking if the user's id was found in database
        if (!$entityId) {
            $this->addFlash('danger', 'ID d\'utilisateur manquant ou inconnu !.');
            return $this->redirect(
                $this->adminUrlGenerator
                    ->setController(UserCrudController::class)
                    ->setAction(Crud::PAGE_INDEX)
                    ->generateUrl()
            );
        }

        // Getting the user thanks to the EntityManager
        $user = $this->entityManager->getRepository(User::class)->find($entityId);

        // Checking if the user's datas were found in database
        if (!$user) {
            $this->addFlash('danger', 'Utilisateur introuvable.');
            return $this->redirect(
                $this->adminUrlGenerator
                    ->setController(UserCrudController::class)
                    ->setAction(Crud::PAGE_INDEX)
                    ->generateUrl()
            );
        }

        // Generating a reset token (valid for 2 hours)
        $resetToken = Uuid::uuid4()->toString();
        $user->setResetPasswordToken($resetToken);
        $user->setResetPasswordExpiresAt(new \DateTimeImmutable('+2 hour'));

        // Registering user's reset token in database
        $this->entityManager->flush();

        // Generating the reset link
        $resetLink = $this->urlGenerator->generate('app_reset_password', [
            'token' => $resetToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // Sending the reset email to the user
        $email = (new Email())
            ->from('no-reply@symfocook.com')
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html("<p>Bonjour,</p><br>
                <p>Cliquez sur le lien ci-dessous pour réinitialiser votre mot de passe :</p>
                <a href='$resetLink'>Réinitialiser mon mot de passe</a><br><br>
                <p>Belle journée,</p>
                <p>L'équipe Symfocook</p>");

        $this->mailer->send($email);

        // Redirects the admin to the user listing page with a message indicating that the email has been sent successfully.
        $this->addFlash('success', 'Email de réinitialisation envoyé avec succès.');
        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(UserCrudController::class)
                ->setAction(Crud::PAGE_INDEX)
                ->generateUrl()
        );
    }
}
