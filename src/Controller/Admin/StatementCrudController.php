<?php

namespace App\Controller\Admin;

use App\Entity\Source;
use App\Entity\Statement;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\File as FileConstraint;

/**
 * @extends AbstractCrudController<Statement>
 */
class StatementCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly string $statementsStoragePath,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Statement::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Statement')
            ->setEntityLabelInPlural('Statements')
            ->setPageTitle(Crud::PAGE_INDEX, 'Statements')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(100)
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        // No SHOW/DETAIL action per requirements; keep defaults (batch delete is available by default)
        return $actions;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(\EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter::new('id'))
            ->add(\EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter::new('processed_at'))
            ->add(\EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter::new('statement_date'))
            ->add(\EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter::new('source')->setFormTypeOptions([
                'value_type_options' => [
                    'choice_label' => 'name',
                ],
            ]));
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id')->onlyOnIndex();
        $filename = TextField::new('filename')->onlyOnIndex();
        $processedAt = DateTimeField::new('processed_at', 'Processed At')->setSortable(true)->onlyOnIndex();
        $statementDate = DateField::new('statement_date', 'Statement Date')->setRequired(false);

        // Show source name on index (sortable/filterable); use Association on forms
        $sourceNameIndex = AssociationField::new('source', 'Source')
            ->onlyOnIndex()
            ->setSortable(true)
            ->setSortProperty('source.name')
            ->formatValue(static function ($value, $entity) {
                return $entity?->getSource()?->getName();
            });
        $sourceAssoc = AssociationField::new('source')
            ->setFormTypeOptions([
                'choice_label' => 'name',
                'placeholder' => 'Select a Source',
                'class' => Source::class,
            ]);

        // Unmapped upload field (create only)
        $uploadField = TextField::new('statement_file', 'Statement file')
            ->setFormType(FileType::class)
            ->setFormTypeOptions([
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new FileConstraint([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'text/csv', 'text/plain', 'application/pdf',
                            'application/vnd.ms-excel', 'application/csv',
                            'text/x-csv', 'text/comma-separated-values', 'text/tab-separated-values',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid CSV or PDF file',
                    ]),
                ],
            ])
            ->onlyOnForms()
            ->onlyWhenCreating();

        $uploadedAt = DateTimeField::new('uploaded_at', 'Uploaded At')->hideOnForm();

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id, $filename, $processedAt, $sourceNameIndex, $statementDate,
            ];
        }

        if (Crud::PAGE_NEW === $pageName) {
            return [
                $uploadField,
                $sourceAssoc,
                $statementDate,
                $uploadedAt,
            ];
        }

        if (Crud::PAGE_EDIT === $pageName) {
            return [
                $sourceAssoc,
                $statementDate,
                $uploadedAt,
            ];
        }

        return [
            $id,
        ];
    }

    public function persistEntity(
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        $entityInstance,
    ): void {
        $request = $this->requestStack->getCurrentRequest();
        $form = Crud::PAGE_NEW === $this->getContext()->getCrud()->getCurrentPage()
            ? $this->getContext()->getRequest()->request
            : null;

        /** @var UploadedFile|null $file */
        $file = $this->getContext()->getRequest()->files->get('Statement')['statement_file'] ?? null;

        if ($file instanceof UploadedFile) {
            $storagePath = $this->statementsStoragePath;
            $filesystem = new Filesystem();
            if (!$filesystem->exists($storagePath)) {
                $filesystem->mkdir($storagePath, 0755);
            }

            $safeName = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
            $mime = (string) $file->getMimeType();
            if (\in_array($mime, ['text/csv', 'application/csv', 'text/plain', 'application/vnd.ms-excel'], true)) {
                $ext = 'csv';
            } elseif ('application/pdf' === $mime) {
                $ext = 'pdf';
            } else {
                $ext = strtolower(trim((string) (pathinfo($file->getClientOriginalName(), \PATHINFO_EXTENSION) ?: $file->guessExtension())));
            }
            if (!\in_array($ext, ['csv', 'pdf'], true)) {
                throw new \LogicException('Invalid file extension, only csv and pdf are allowed');
            }
            $uniqueName = $safeName.'-'.bin2hex(random_bytes(6)).'.'.$ext;
            $file->move($storagePath, $uniqueName);

            $entityInstance->setFilename($uniqueName);
            $entityInstance->setUploadedAt(new \DateTimeImmutable());
        } else {
            // If no file provided on create, keep default behavior
            if (null === $entityInstance->getUploadedAt()) {
                $entityInstance->setUploadedAt(new \DateTimeImmutable());
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        $filename = $entityInstance->getFilename();
        if ($filename) {
            $path = rtrim($this->statementsStoragePath, '/').'/'.$filename;
            $filesystem = new Filesystem();
            if ($filesystem->exists($path)) {
                $filesystem->remove($path);
            }
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
