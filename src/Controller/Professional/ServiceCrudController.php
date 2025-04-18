<?php

namespace App\Controller\Professional;

use App\Entity\Service;
use App\Entity\Professionnal;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Symfony\Bundle\SecurityBundle\Security;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;

class ServiceCrudController extends AbstractCrudController
{
    private Security $security;

    public function __construct(Security $security, private EntityRepository $entityRepository)
    {
        $this->security = $security;
    }

    public static function getEntityFqcn(): string
    {
        return Service::class;
    }

    // Filtrer les services pour n'afficher que ceux du professionnel connecté
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        /** @var User */
        $user = $this->security->getUser();
        $professional = $user->getProfessional();

        $response = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $response->andWhere('entity.professional = :professional')->setParameter('professional', $professional);

        return $response;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            Field::new('name', 'Nom du service')
                ->setFormTypeOptions([
                    'label' => 'Nom du service'
                ]),
            Field::new('description', 'Description')
                ->setFormTypeOptions([
                    'label' => 'Description du service'
                ]),
            MoneyField::new('price', 'Prix')
                ->setCurrency('EUR')
                ->setFormTypeOptions([
                    'label' => 'Prix du service'
                ]),
        ];

        return $fields;
    }

    // Rattacher le professionnel qui crée le service
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Service) return;

        /** @var Professionnal */
        $user = $this->security->getUser();
        $professional = $user->getProfessional();

        if ($professional) {
            $entityInstance->setProfessional($professional);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function configureActions(Actions $actions): Actions
{
    return $actions
        ->setPermission(Action::EDIT, 'SERVICE_EDIT')
        ->setPermission(Action::DELETE, 'SERVICE_DELETE')
        ->setPermission(Action::DETAIL, 'SERVICE_VIEW');
}
}
