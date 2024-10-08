<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\DateUpdate\CreatedAtUpdatable;
use App\Entity\DateUpdate\UpdatedAtUpdatable;
use App\Repository\MinimalRemainRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Минимальный остаток товара по законодательству.
 *
 * @ORM\Entity(MinimalRemainRepository::class)
 * @ORM\Table(
 *     name="prov_minimal_remains",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="article_uniq", columns={"article"})
 *     }
 * )
 */
class MinimalRemain implements CreatedAtUpdatable, UpdatedAtUpdatable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="id")
     */
    private int $id;

    /**
     * Артикул товара.
     *
     * @ORM\Column(type="string", length=32)
     */
    private string $article;

    /**
     * Минимальный остаток для товара.
     *
     * @ORM\Column(type="integer")
     */
    private int $minimalRemainQuantity;

    /**
     * Дата создания.
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $createdAt;

    /**
     * Дата последнего обновления.
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getArticle(): string
    {
        return $this->article;
    }

    public function setArticle(string $article): self
    {
        $this->article = $article;

        return $this;
    }

    public function getMinimalRemainQuantity(): int
    {
        return $this->minimalRemainQuantity;
    }

    public function setMinimalRemainQuantity(int $minimalRemainQuantity): self
    {
        $this->minimalRemainQuantity = $minimalRemainQuantity;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
