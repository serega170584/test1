<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\OrderReserve;
use App\Entity\RemainReserve;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class RemainReserveManager
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Пересчитывает общий резерв товара на точке.
     *
     * @param int $sign Сложить / вычесть резерв
     */
    public function recalculate(OrderReserve $orderReserve, int $sign = 1): void
    {
        $repo = $this->em->getRepository(RemainReserve::class);

        /** @var RemainReserve $remainReserve */
        $remainReserve = $repo->findOneBy([
            'article' => $orderReserve->getArticle(),
            'storeId' => $orderReserve->getStoreId(),
        ]);

        if (!$remainReserve) {
            $remainReserve = (new RemainReserve())
                ->setStoreId($orderReserve->getStoreId())
                ->setArticle($orderReserve->getArticle())
                ->setQuantity(0);
        }

        // На сколько должен измениться резерв
        $deltaQuantity = $sign * $orderReserve->getQuantity();

        // Пересчитанный резерв
        $quantity = $remainReserve->getQuantity() + $deltaQuantity;

        // Логируем странный уход в отрицательный остаток
        if ($quantity < 0) {
            $this->logger->warning(
                sprintf(
                    'reserves.recalculate: negative reserve (%s) detected for store=%s, article=%s after recalculate.',
                    $quantity,
                    $remainReserve->getStoreId(),
                    $remainReserve->getArticle()
                ),
                [
                    'delta_quantity' => $deltaQuantity,
                    'order_id' => $orderReserve->getOrderId(),
                ]
            );
        }

        // В отрицательные остатки не уходим
        $remainReserve
            ->setUpdatedAt(new \DateTime())
            ->setQuantity(max($quantity, 0));

        $this->em->persist($remainReserve);
    }

    /**
     * Возвращает список резервов по указанным магазинам ({store_id}_{article} => quantity).
     */
    public function getStoreReserves(array $stores): array
    {
        if (!$stores) {
            return [];
        }

        $repo = $this->em->getRepository(RemainReserve::class);

        $reserves = $repo->createQueryBuilder('rr')
                         ->where('rr.storeId IN (:stores)')->setParameter('stores', $stores)
                         ->getQuery()
                         ->toIterable();

        $result = [];

        /** @var RemainReserve $reserve */
        foreach ($reserves as $reserve) {
            $key = $this->getReserveKey($reserve->getStoreId(), $reserve->getArticle());
            $result[$key] = $reserve->getQuantity();
            $this->em->detach($reserve);
        }

        return $result;
    }

    /**
     * Возвращает ключ записи резерва.
     */
    public function getReserveKey(string $storeId, string $article): string
    {
        return $storeId . '_' . $article;
    }
}
