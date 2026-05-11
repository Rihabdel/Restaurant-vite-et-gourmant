<?php

namespace App\Repository;

use App\Entity\Orders;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Orders>
 */
class OrdersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Orders::class);
    }

    // trouver les commandes d'un client
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->leftJoin('o.menu', 'm')
            ->addSelect('m');

        if (isset($filters['user'])) {
            $qb->andWhere('o.user = :userId')
                ->setParameter('userId', $filters['user']);
        }

        if (isset($filters['status'])) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (isset($filters['date_range'])) {
            $qb->andWhere('o.deliveryDate BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', new \DateTime($filters['date_range']['start']))
                ->setParameter('endDate', new \DateTime($filters['date_range']['end']));
        }
        if (isset($filters['menu'])) {
            $qb->andWhere('o.menu = :menu')
                ->setParameter('menu', $filters['menu']);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByClientId(int $userId)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :userId')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->leftjoin('o.menu', 'm')
            ->addSelect('m')
            ->setParameter('userId', $userId)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    // trouver les commandes par status (pour employee)
    public function findByStatus(string $status)
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->leftJoin('o.menu', 'm')
            ->addSelect('m')
            ->andWhere('o.status = :status')
            ->setParameter('status', $status)
            ->orderBy('o.deliveryDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
    //statistiques des commandes pour l'addministration par menu
    public function getOrderStatisticsByMenu(\DateTime $startDate, \DateTime $endDate)
    {

        return $this->createQueryBuilder('o')
            ->select('m.title AS menuTitle, COUNT(o.id) AS orderCount')
            ->leftJoin('o.menu', 'm')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('m.id')
            ->getQuery()
            ->getResult();
    }


    //calculer le chiffre d'affaires total pour une période donnée
    public function calculateTotalRevenue(
        \DateTime $startDate,
        \DateTime $endDate
    ) {
        return $this->createQueryBuilder('o')
            ->select('SUM(o.totalPrice) AS totalRevenue')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
