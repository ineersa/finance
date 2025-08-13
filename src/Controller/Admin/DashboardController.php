<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Source;
use App\Entity\Statement;
use App\Entity\Transaction;
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

        yield MenuItem::section('Content');
        yield MenuItem::linkToCrud('Categories', 'fa fa-solid fa-tags', Category::class);
        yield MenuItem::linkToCrud('Sources', 'fa fa-solid fa-database', Source::class);
        yield MenuItem::linkToCrud('Statements', 'fa fa-solid fa-file', Statement::class);
        yield MenuItem::linkToCrud('Transactions', 'fa fa-solid fa-money-bill', Transaction::class);

        yield MenuItem::section('Administrative');
        yield MenuItem::linkToCrud('Users', 'fa fa-solid fa-users', User::class);
    }
}
