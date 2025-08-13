<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Statement;
use App\Entity\Transaction;
use App\Enum\TransactionTypeEnum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Transaction>
 */
class TransactionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Transaction::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Transaction')
            ->setEntityLabelInPlural('Transactions')
            ->setPageTitle(Crud::PAGE_INDEX, 'Transactions')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(100)
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        // Disable SHOW and EDIT per requirements; keep DELETE and batch delete
        return $actions
            ->disable(Action::EDIT, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        // statement (select by filename), category (by name), date, amount, amount_usd, type
        return $filters
            ->add(\EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter::new('statement')->setFormTypeOptions([
                'value_type_options' => [
                    'choice_label' => 'filename',
                    'class' => Statement::class,
                ],
            ]))
            ->add(\EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter::new('category')->setFormTypeOptions([
                'value_type_options' => [
                    'choice_label' => 'name',
                    'class' => Category::class,
                ],
            ]))
            ->add(\EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter::new('date'))
            ->add(\EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter::new('amount'))
            ->add(\EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter::new('amount_usd'))
            ->add(\EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter::new('type')->setChoices([
                'Credit' => TransactionTypeEnum::Credit,
                'Debit' => TransactionTypeEnum::Debit,
            ]));
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id')->onlyOnIndex();

        // Statement filename on index
        $statementIndex = AssociationField::new('statement', 'Statement')
            ->onlyOnIndex()
            ->formatValue(static function ($value, $entity) {
                return $entity?->getStatement()?->getFilename();
            });
        $statementAssoc = AssociationField::new('statement')
            ->setFormTypeOptions([
                'choice_label' => 'filename',
                'placeholder' => 'Select a Statement',
                'class' => Statement::class,
            ])
            ->onlyOnForms();

        $date = DateField::new('date', 'Date');

        // amount with currency on index
        $amountWithCurrency = TextField::new('amount', 'Amount')
            ->onlyOnIndex()
            ->formatValue(static function ($value, $entity) {
                $currency = $entity?->getCurrency();
                $amount = $entity?->getAmount();
                if (null === $amount) {
                    return '';
                }

                return null !== $currency ? \sprintf('%s %s', strtoupper((string) $currency), $amount) : (string) $amount;
            });

        // Separate amount and currency for forms
        $amount = NumberField::new('amount')
            ->setNumDecimals(2)
            ->setHelp('Enter numeric amount, e.g. 123.45');
        $currency = TextField::new('currency')
            ->setHelp('3-letter currency code, e.g. USD, CAD, EUR');

        // amount USD on index
        $amountUsd = NumberField::new('amount_usd', 'Amount USD')
            ->setNumDecimals(2)
            ->setStoredAsString(true)
            ->onlyOnIndex();

        $typeIndex = TextField::new('type.value', 'Type')
            ->onlyOnIndex()
            ->formatValue(static function ($value) {
                return ucfirst((string) $value);
            });
        // Use EnumType for robust enum handling in forms
        $typeForm = ChoiceField::new('type')
            ->setFormType(\Symfony\Component\Form\Extension\Core\Type\EnumType::class)
            ->setFormTypeOptions([
                'class' => TransactionTypeEnum::class,
                'choice_label' => static function (TransactionTypeEnum $choice): string {
                    return ucfirst(strtolower($choice->value));
                },
            ])
            ->renderExpanded(false)
            ->allowMultipleChoices(false);

        // Category column on index (show name) and association on form
        $categoryIndex = AssociationField::new('category', 'Category')
            ->onlyOnIndex()
            ->setTemplatePath('admin/field/category_with_icon.html.twig');

        $categoryAssoc = AssociationField::new('category')
            ->setFormTypeOptions([
                'choice_label' => 'name',
                'placeholder' => 'Select a Category',
                'class' => Category::class,
                'required' => true,
                'choice_attr' => static function (Category $category) {
                    return ['data-icon' => $category->getIcon()];
                },
            ])
            ->setLabel('Category')
            ->onlyOnForms();

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id, $statementIndex, $date, $amountWithCurrency, $amountUsd, $typeIndex, $categoryIndex,
            ];
        }

        if (Crud::PAGE_NEW === $pageName) {
            return [
                $statementAssoc, $date, $amount, $currency, $typeForm, $categoryAssoc,
            ];
        }

        throw new \LogicException('Unsupported action');
    }

    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        // Auto-assign source from statement and default category if missing
        $statement = $entityInstance->getStatement();
        if ($statement instanceof Statement) {
            if (null === $entityInstance->getSource()) {
                $entityInstance->setSource($statement->getSource());
            }
        }

        if (null === $entityInstance->getCategory()) {
            // Try to pick a default category named "Other Transactions"
            $defaultCategory = $entityManager->getRepository(Category::class)->findOneBy(['name' => 'Other Transactions']);
            if ($defaultCategory instanceof Category) {
                $entityInstance->setCategory($defaultCategory);
            } else {
                // fallback: pick any existing category
                $anyCategory = $entityManager->getRepository(Category::class)->findOneBy([]);
                if ($anyCategory instanceof Category) {
                    $entityInstance->setCategory($anyCategory);
                }
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }
}
