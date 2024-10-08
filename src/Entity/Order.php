<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\Table(name="`order`")
 */
class Order
{
    /**
     * ID заказа.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * Признак наличия в заказе "Доставки до клиента".
     *
     * @ORM\Column(type="boolean")
     */
    private bool $isDeliveryToCustomer;

    /**
     * Признак ассортимента от дистрибьютора в заказе.
     *
     * @ORM\Column(type="boolean")
     */
    private bool $isDistributor;

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isDeliveryToCustomer(): bool
    {
        return $this->isDeliveryToCustomer;
    }

    public function setIsDeliveryToCustomer(bool $isDeliveryToCustomer): self
    {
        $this->isDeliveryToCustomer = $isDeliveryToCustomer;

        return $this;
    }

    public function isDistributor(): bool
    {
        return $this->isDistributor;
    }

    public function setIsDistributor(bool $isDistributor): self
    {
        $this->isDistributor = $isDistributor;

        return $this;
    }
}
