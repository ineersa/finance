<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/home', routeName: 'home')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly Packages $packages,
    ) {
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addAssetMapperEntry('admin');
    }

    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Finance dashboard')
            ->setFaviconPath($this->packages->getUrl('icons/favicon.svg'));
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Administrative');
        yield MenuItem::linkToCrud('Users', 'fa fa-solid fa-users', User::class);
    }
}
