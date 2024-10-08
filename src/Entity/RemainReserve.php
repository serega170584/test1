<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Общий резерв остатков (агрегация).
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="remain_reserves",
 *     indexes={
 *         @ORM\Index(name="search_idx", columns={"store_id", "article"}),
 *         @ORM\Index(name="updated_at_idx", columns={"updated_at"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="remain_reserves_uniq", columns={"store_id", "article"})
 *     }
 * )
 */
class RemainReserve
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
     * Количество в резерве.
     *
     * @ORM\Column(type="integer")
     */
    private int $quantity;

    /**
     * Дата последнего обновления.
     *
     * @ORM\Column(type="datetime")
     */
    private \DateTime $updatedAt;

    public function __construct()
    {
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
