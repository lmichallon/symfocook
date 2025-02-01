<?php

namespace App\EventListener;

use App\Event\SendMailEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SendMailListener
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

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
}