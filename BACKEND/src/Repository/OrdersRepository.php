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
    public function findWithFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->leftJoin('o.menu', 'm')
            ->addSelect('m');

        if (isset($filters['userId'])) {
            $qb->andWhere('o.user = :userId')
                ->setParameter('userId', $filters['userId']);
        }

        if (isset($filters['status'])) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (isset($filters['delivery_date'])) {
            $qb->andWhere('o.deliveryDate = :deliveryDate')
                ->setParameter('deliveryDate', new \DateTime($filters['delivery_date']));
        }

        return $qb->getQuery()->getResult();
    }

    public function findByClientId($userId)
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
    public function findByStatus($status)
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
            ->select('m.name AS menuTitle, COUNT(o.id) AS orderCount')
            ->leftJoin('o.menu', 'm')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('m.id')
            ->getQuery()
            ->getResult();
    }
    // statistiques des commandes pour l'addministration par plat
    public function getOrderStatisticsByDish(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->createQueryBuilder('o')
            ->select('d.name AS dishName, COUNT(o.id) AS orderCount')
            ->leftJoin('o.menu', 'm')
            ->leftJoin('m.dishes', 'd')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('d.id')
            ->getQuery()
            ->getResult();
    }
    // compter le nombre de commandes par jour pour une période donnée
    public function countOrdersByDay(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->createQueryBuilder('o')
            ->select('DATE(o.createdAt) AS orderDate, COUNT(o.id) AS orderCount')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('orderDate')
            ->orderBy('orderDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
    // compter le nombre de commandes par mois pour une période donnée
    public function countOrdersByMonth(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->createQueryBuilder('o')
            ->select('MONTH(o.createdAt) AS orderMonth, YEAR(o.createdAt) AS orderYear, COUNT(o.id) AS orderCount')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('orderYear, orderMonth')
            ->orderBy('orderYear', 'ASC')
            ->addOrderBy('orderMonth', 'ASC')
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
