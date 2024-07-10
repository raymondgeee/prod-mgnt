<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Product;
use Doctrine\ORM\QueryBuilder;

class ProductService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function countProducts(): int
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('count(p.id)')
           ->from(Product::class, 'p');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getFilters($filters): QueryBuilder
    {
        $queryBuilder = $this->entityManager->getRepository(Product::class)->createQueryBuilder('p');

        if ($filters['product_name'] != "") {
            $queryBuilder->andWhere('p.name LIKE :name')->setParameter('name', '%' . $filters['product_name'] . '%');
        }

        if ($filters['product_desc'] != "") {
            $queryBuilder->andWhere('p.description LIKE :description')->setParameter('description', '%' . $filters['product_desc'] . '%');
        }

        if ($filters['price_min'] != "") {
            $queryBuilder->andWhere('p.price >= :priceMin')->setParameter('priceMin', $filters['price_min']);
        }

        if ($filters['price_max'] != "") {
            $queryBuilder->andWhere('p.price <= :priceMax')->setParameter('priceMax', $filters['price_max']);
        }

        if ($filters['stock_min'] != "") {
            $queryBuilder->andWhere('p.stockQuantity >= :stockMin')->setParameter('stockMin', $filters['stock_min']);
        }

        if ($filters['stock_max'] != "") {
            $queryBuilder->andWhere('p.stockQuantity <= :stockMax')->setParameter('stockMax', $filters['stock_max']);
        }

        if ($filters['date_from'] != "") {
            $queryBuilder->andWhere('p.createdDatetime >= :dateFrom')->setParameter('dateFrom', new \DateTime($filters['date_from']));
        }

        if ($filters['date_to'] != "") {
            $queryBuilder->andWhere('p.createdDatetime <= :dateTo')->setParameter('dateTo', new \DateTime($filters['date_to']));
        }

        return $queryBuilder;
    }
}
