<?php

namespace App\Repository;

use App\Entity\ContactMsg;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Override;

/**
 * @extends ServiceEntityRepository<ContactMsg>
 */
class ContactMsgRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactMsg::class);
    }
    #[Override]
    public function findAll(): array
    {
        return parent::findAll();
    }
}
