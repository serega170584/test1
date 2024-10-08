<?php

declare(strict_types=1);

namespace App\Tests\Support\Generator;

use App\Request\Dto\OrderStatusInterface as ImOrderStatus;
use App\Request\Dto\PROVIDER\OrderStatusInterface as PROVIDEROrderStatus;
use App\Tests\Support\Dto\OrderDto;
use Exception;
use Symfony\Component\Uid\Uuid;

class OrderDtoGenerator
{
    private CartDtoGenerator $cartDtoGenerator;

    private bool $withCart = false;

    private bool $withAddressDelivery = false;

    private bool $withAcceptCode = false;

    private bool $withSid = false;

    private bool $withRecipeConfirm = false;

    private bool $withMarkingCodes = false;

    private bool $withEmptyReserved = false;

    private bool $withRecipe = false;

    private bool $withDateOrderExecution = false;

    public function __construct()
    {
        $this->cartDtoGenerator = new CartDtoGenerator();
    }

    /**
     * @throws Exception
     */
    public function generate(): OrderDto
    {
        if ($this->withRecipeConfirm) {
            $this->cartDtoGenerator->withRecipeConfirm();
        }
        if ($this->withMarkingCodes) {
            $this->cartDtoGenerator->withMarkingCodes();
        }
        if ($this->withEmptyReserved) {
            $this->cartDtoGenerator->withEmptyReserved();
        }
        if ($this->withRecipe) {
            $this->cartDtoGenerator->withRecipe();
        }

        $orderDto = (new OrderDto())
            ->setRequestId(Uuid::v4()->toRfc4122())
            ->setOrderId((string)random_int(1000000, 9999999))
            ->setImStatus(ImOrderStatus::STATUS_CREATED)
            ->setPROVIDERStatus(PROVIDEROrderStatus::STATUS_CREATED)
            ->setStoreId((string)random_int(1000000, 9999999))
            ->setDateOrder(date('Y-m-d'))
            ->setDateProviding(date('Y-m-d'))
            ->setPhone('79' . random_int(100000000, 999999999))
            ->setDivisionPost('division-post')
            ->setPartnerName('partner-name')
            ->setComment('comment')
            ->setPROVIDERUserEdit('user-edit')
            ->setPROVIDERTimeEdit(date('d.m.Y H:i:s'));

        if ($this->withAddressDelivery) {
            $orderDto->setAddressDelivery('address-delivery');
        }
        if ($this->withAcceptCode) {
            $orderDto->setAcceptCode((string)random_int(1000, 9999));
        }
        if ($this->withCart) {
            $orderDto->setCartDto($this->cartDtoGenerator->generate());
        }
        if ($this->withSid) {
            $orderDto->setSid(bin2hex(random_bytes(10)));
        }
        if ($this->withDateOrderExecution) {
            $orderDto->setDateOrderExecution(date('Y-m-d'));
        }

        return $orderDto;
    }

    /**
     * @return OrderDtoGenerator
     */
    public function withCart(): OrderDtoGenerator
    {
        $this->withCart = true;
        return $this;
    }

    /**
     * @return OrderDtoGenerator
     */
    public function withAddressDelivery(): OrderDtoGenerator
    {
        $this->withAddressDelivery = true;
        return $this;
    }

    /**
     * @return OrderDtoGenerator
     */
    public function withAcceptCode(): OrderDtoGenerator
    {
        $this->withAcceptCode = true;
        return $this;
    }

    /**
     * @return OrderDtoGenerator
     */
    public function withSid(): OrderDtoGenerator
    {
        $this->withSid = true;
        return $this;
    }

    /**
     * @return OrderDtoGenerator
     */
    public function withRecipeConfirm(): OrderDtoGenerator
    {
        $this->withRecipeConfirm = true;
        return $this;
    }

    /**
     * @return OrderDtoGenerator
     */
    public function withMarkingCodes(): OrderDtoGenerator
    {
        $this->withMarkingCodes = true;
        return $this;
    }

    /**
     * @return OrderDtoGenerator
     */
    public function withEmptyReserved(): OrderDtoGenerator
    {
        $this->withEmptyReserved = true;
        return $this;
    }

    /**
     * @return OrderDtoGenerator
     */
    public function withRecipe(): OrderDtoGenerator
    {
        $this->withRecipe = true;
        return $this;
    }

    /**
     * @return OrderDtoGenerator
     */
    public function withDateOrderExecution(): OrderDtoGenerator
    {
        $this->withDateOrderExecution = true;
        return $this;
    }
}