<?php

namespace App\Event;

use App\Entity\User;


class SendMailEvent
{
    public const NAME = 'user.registered';
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}