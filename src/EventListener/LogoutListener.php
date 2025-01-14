<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $session = $request->getSession();
            if ($session) {
                $session->getFlashBag()->add('success', 'Vous avez bien été déconnecté.');
            }
        }
    }
}