<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderReserve;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OrderReserve|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderReserve|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderReserve[] findAll()
 * @method OrderReserve[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderReserveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderReserve::class);
    }

    public function save(OrderReserve $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function delete(OrderReserve $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Вернет истекшие резервы для заказов дистрибьюторам.
     *
     * @param int $days Количество прошедших дней с постановки резерва
     */
    public function findOutdatedOnlyDistributorsByPassedDays(int $days): iterable
    {
        $dateRemoving = new DateTime("-{$days} day");

        return $this->createQueryBuilder('ores')
                    ->leftJoin(Order::class, 'o', Join::WITH, 'o.id = ores.orderId')
                    ->where('o.isDistributor=true')
                    ->andWhere('ores.createdAt < :createdAt')
                    ->setParameter('createdAt', $dateRemoving)
                    ->getQuery()
                    ->toIterable();
    }

    /**
     * Вернет истекшие резервы для всех типов заказов, кроме дистрибьюторов.
     *
     * @param int $days Количество прошедших дней с постановки резерва
     */
    public function findOutdatedWithoutDistributorsByPassedDays(int $days): iterable
    {
        $dateRemoving = new DateTime("-{$days} day");

        return $this->createQueryBuilder('ores')
                    ->leftJoin(Order::class, 'o', Join::WITH, 'o.id = ores.orderId')
                    ->where('o.isDistributor IS NULL OR o.isDistributor = false')
                    ->andWhere('ores.createdAt < :createdAt')
                    ->setParameter('createdAt', $dateRemoving)
                    ->getQuery()
                    ->toIterable();
    }
}
