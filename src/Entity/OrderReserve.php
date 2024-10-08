<?php

namespace App\Entity;

use App\Repository\OrderReserveRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Резерв позиций по заказу.
 *
 * @ORM\Entity(repositoryClass=OrderReserveRepository::class)
 * @ORM\Table(
 *     name="order_reserves",
 *     indexes={
 *         @ORM\Index(name="search_idx", columns={"store_id", "article", "order_id"}),
 *         @ORM\Index(name="order_idx", columns={"order_id"}),
 *         @ORM\Index(name="created_at_idx", columns={"created_at"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="order_reserves_uniq", columns={"order_id", "store_id", "article"})
 *     }
 * )
 */
class OrderReserve
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="id")
     */
    private int $id;

    /**
     * XML ID аптеки.
     *
     * @ORM\Column(type="string", length=32)
     */
    private string $storeId;

    /**
     * Артикул товара.
     *
     * @ORM\Column(type="string", length=32)
     */
    private string $article;

    /**
     * Номер заказа.
     *
     * @ORM\Column(type="integer")
     */
    private int $orderId;

    /**
     * Количество в резерве.
     *
     * @ORM\Column(type="integer")
     */
    private int $quantity;

    /**
     * Дата создания.
     *
     * @ORM\Column(type="datetime")
     */
    private \DateTime $createdAt;

    /**
     * Дата последнего обновления.
     *
     * @ORM\Column(type="datetime")
     */
    private \DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getStoreId(): string
    {
        return $this->storeId;
    }

    /**
     * @return $this
     */
    public function setStoreId(string $storeId): self
    {
        $this->storeId = $storeId;

        return $this;
    }

    public function getArticle(): string
    {
        return $this->article;
    }

    /**
     * @return $this
     */
    public function setArticle(string $article): self
    {
        $this->article = $article;

        return $this;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * @return $this
     */
    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return $this
     */
    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return $this
     */
    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
