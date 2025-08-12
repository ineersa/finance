<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Category')
            ->setEntityLabelInPlural('Categories')
            ->setPageTitle(Crud::PAGE_INDEX, 'Categories')
            ->setDefaultSort(['id' => 'ASC'])
            ->setPaginatorPageSize(50);
    }


    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield TextField::new('icon')
            ->setTemplatePath('admin/field/icon_field.html.twig');
        yield TextField::new('name');
        yield TextareaField::new('description');
    }
}
