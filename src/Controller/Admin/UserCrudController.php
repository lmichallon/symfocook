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
use Symfony\Bundle\SecurityBundle\Security;
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
    private Security $security;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager,
        AdminUrlGenerator $adminUrlGenerator,
        Security $security
    )
    {
        $this->passwordHasher = $passwordHasher;
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->security = $security;
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


        return $actions
            ->add(Crud::PAGE_EDIT, $resetPassword)
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->displayIf(function ($entity) {
                    // Preventing own deletion of the logged-in user's account
                    return $entity->getId() !== $this->security->getUser()->getId();
                });
            });
    }

    // Sending a mail to user with a link for reset his password
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

    // Getting the user concerned by the password reset request
    private function getUserFromContext(AdminContext $context): ?User
    {
        $entityId = $context->getRequest()->query->get('entityId');

        $user = $this->entityManager->getRepository(User::class)->find($entityId);

        return $user;
    }

    // Generating and saving user's reset token
    private function generateAndSaveResetToken(User $user): string
    {
        $resetToken = Uuid::uuid4()->toString();
        $user->setResetPasswordToken($resetToken);
        $user->setResetPasswordExpiresAt(new \DateTimeImmutable('+2 hour'));

        $this->entityManager->flush();

        return $resetToken;
    }

    // Sending the reset email to user
    private function sendResetEmail(User $user, string $resetToken): void
    {
        $resetLink = $this->urlGenerator->generate('app_reset_password', [
            'token' => $resetToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from('no-reply@symfocook.com')
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html("<p>Bonjour, un administrateur de notre site à demandé à ce que vous réinitialisiez votre mot de passe.</p><br>
            <p>Cliquez sur le lien ci-dessous pour réinitialiser votre mot de passe :</p>
            <a href='$resetLink'>Réinitialiser mon mot de passe</a><br><br>
            <p>Belle journée,</p>
            <p>L'équipe Symfocook</p>");

        $this->mailer->send($email);
    }

    // Redirecting with a message
    private function redirectWithMessage(string $message, bool $isSuccess): Response
    {
        $flashType = $isSuccess ? 'success' : 'danger';

        $this->addFlash($flashType, $message);

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(UserCrudController::class)
                ->setAction(Crud::PAGE_INDEX)
                ->generateUrl()
        );
    }
}
