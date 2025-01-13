<?php

namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardBackOfficeController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
         // Redirects immmediatly to managing recipes' interface
         $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
         return $this->redirect($adminUrlGenerator->setController(RecipeCrudController::class)->generateUrl());
    }

    // User logout from back-office
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
    }

    // Allows to configure dashboard's params
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SymfoCook - Back Office');
    }

    // Allows to configure dashboard's navmenu
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-door-open');

        yield MenuItem::section('Gestion des utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-user', User::class);

        yield MenuItem::section('Gestion des recettes');
        yield MenuItem::linkToCrud('Recettes', 'fa fa-utensils', Recipe::class);
    }
}
