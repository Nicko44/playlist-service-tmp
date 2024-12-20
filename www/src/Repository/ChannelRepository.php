<?php

namespace App\Repository;

use App\Entity\Channel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

/**
 * @method Channel|null find($id, $lockMode = null, $lockVersion = null)
 * @method Channel|null findOneBy(array $criteria, array $orderBy = null)
 * @method Channel[]    findAll()
 * @method Channel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChannelRepository extends ServiceEntityRepository
{
    private $manager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $manager)
    {
        parent::__construct($registry, Channel::class);
    }
    

    /**
    * @return Channel[] Returns an array of Channel objects with ordering by category sequence and channel sequence
    */
    public function findAllOrderBySequence($category): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.category = :category')
            ->setParameter('category', $category)
            ->orderBy('c.sequence', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(Channel $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Channel $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
