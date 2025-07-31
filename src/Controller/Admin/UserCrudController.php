<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @extends AbstractCrudController<User>
 */
class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn (Action $action) => $action
                ->setLabel('← Back')
                ->setCssClass('btn btn-secondary')
            )
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->update(Crud::PAGE_NEW, Action::INDEX, fn (Action $action) => $action
                ->setLabel('← Back')
                ->setCssClass('btn btn-secondary')
            );
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users')
            ->setPageTitle(Crud::PAGE_INDEX, 'Users');
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id');
        $email = EmailField::new('email');

        $password = TextField::new('password', 'Password')
            ->setFormType(PasswordType::class)
            ->setFormTypeOption('toggle', true)
            ->setFormTypeOption('use_toggle_form_theme', true)
            ->setFormTypeOption('toggle_container_classes', ['toggle-password-container'])
            ->setFormTypeOption('attr', ['data-controller' => 'admin--password-generator'])
            ->onlyOnForms();

        if (Crud::PAGE_INDEX === $pageName) {
            yield $id;
            yield $email;
        } elseif (Crud::PAGE_NEW === $pageName) {
            yield $email;
            yield $password;
        } elseif (Crud::PAGE_EDIT === $pageName) {
            yield $password;
        } else {
            yield $email;
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $this->encodePassword($entityInstance);
        }
        $entityInstance->setRoles(['ROLE_ADMIN']);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User && $entityInstance->getPassword()) {
            $this->encodePassword($entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function encodePassword(User $user): void
    {
        $plain = $user->getPassword();

        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $plain)
        );
    }
}
