<?php

namespace App\EventListener;

use App\Entity\User;
use App\Event\SendMailEvent;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordListener
{
    private UserPasswordHasherInterface $passwordHasher;
    private EventDispatcherInterface $dispatcher;

    public function __construct(UserPasswordHasherInterface $passwordHasher, EventDispatcherInterface $dispatcher)
    {
        $this->passwordHasher = $passwordHasher;
        $this->dispatcher = $dispatcher;
    }

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

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        // Check if the password field is being updated
        if ($args->hasChangedField('password')) {
            $this->hashPassword($entity);

            // Recompute changeset to ensure Doctrine is aware of the changes
            $entityManager = $args->getObjectManager();
            $entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet(
                $entityManager->getClassMetadata(User::class),
                $entity
            );
        }
    }
}