<?php

declare(strict_types=1);

namespace App\Tests\Support\Dto;

class OrderDto
{
    private ?CartDto $cartDto = null;

    private string $requestId;

    private string $orderId;

    private string $imStatus;

    private int $PROVIDERStatus;

    private string $dateOrder;

    private string $dateProviding;

    private string $phone;

    private string $storeId;

    private string $divisionPost;

    private string $comment;

    private string $partnerName;

    private string $PROVIDERTimeEdit;

    private string $PROVIDERUserEdit;

    private ?string $addressDelivery = null;

    private ?string $acceptCode = null;

    private ?string $sid = null;

    private ?string $dateOrderExecution = null;

    /**
     * @return CartDto|null
     */
    public function getCartDto(): ?CartDto
    {
        return $this->cartDto;
    }

    /**
     * @param CartDto|null $cartDto
     * @return OrderDto
     */
    public function setCartDto(?CartDto $cartDto): OrderDto
    {
        $this->cartDto = $cartDto;
        return $this;
    }

    /**
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * @param string $requestId
     * @return OrderDto
     */
    public function setRequestId(string $requestId): OrderDto
    {
        $this->requestId = $requestId;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     * @return OrderDto
     */
    public function setOrderId(string $orderId): OrderDto
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return string
     */
    public function getImStatus(): string
    {
        return $this->imStatus;
    }

    /**
     * @param string $imStatus
     * @return OrderDto
     */
    public function setImStatus(string $imStatus): OrderDto
    {
        $this->imStatus = $imStatus;
        return $this;
    }

    /**
     * @return int
     */
    public function getPROVIDERStatus(): int
    {
        return $this->PROVIDERStatus;
    }

    /**
     * @param int $PROVIDERStatus
     * @return OrderDto
     */
    public function setPROVIDERStatus(int $PROVIDERStatus): OrderDto
    {
        $this->PROVIDERStatus = $PROVIDERStatus;
        return $this;
    }

    /**
     * @return string
     */
    public function getDateOrder(): string
    {
        return $this->dateOrder;
    }

    /**
     * @param string $dateOrder
     * @return OrderDto
     */
    public function setDateOrder(string $dateOrder): OrderDto
    {
        $this->dateOrder = $dateOrder;
        return $this;
    }

    /**
     * @return string
     */
    public function getDateProviding(): string
    {
        return $this->dateProviding;
    }

    /**
     * @param string $dateProviding
     * @return OrderDto
     */
    public function setDateProviding(string $dateProviding): OrderDto
    {
        $this->dateProviding = $dateProviding;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     * @return OrderDto
     */
    public function setPhone(string $phone): OrderDto
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return string
     */
    public function getStoreId(): string
    {
        return $this->storeId;
    }

    /**
     * @param string $storeId
     * @return OrderDto
     */
    public function setStoreId(string $storeId): OrderDto
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @return string
     */
    public function getDivisionPost(): string
    {
        return $this->divisionPost;
    }

    /**
     * @param string $divisionPost
     * @return OrderDto
     */
    public function setDivisionPost(string $divisionPost): OrderDto
    {
        $this->divisionPost = $divisionPost;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return OrderDto
     */
    public function setComment(string $comment): OrderDto
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return string
     */
    public function getPartnerName(): string
    {
        return $this->partnerName;
    }

    /**
     * @param string $partnerName
     * @return OrderDto
     */
    public function setPartnerName(string $partnerName): OrderDto
    {
        $this->partnerName = $partnerName;
        return $this;
    }

    /**
     * @return string
     */
    public function getPROVIDERTimeEdit(): string
    {
        return $this->PROVIDERTimeEdit;
    }

    /**
     * @param string $PROVIDERTimeEdit
     * @return OrderDto
     */
    public function setPROVIDERTimeEdit(string $PROVIDERTimeEdit): OrderDto
    {
        $this->PROVIDERTimeEdit = $PROVIDERTimeEdit;
        return $this;
    }

    /**
     * @return string
     */
    public function getPROVIDERUserEdit(): string
    {
        return $this->PROVIDERUserEdit;
    }

    /**
     * @param string $PROVIDERUserEdit
     * @return OrderDto
     */
    public function setPROVIDERUserEdit(string $PROVIDERUserEdit): OrderDto
    {
        $this->PROVIDERUserEdit = $PROVIDERUserEdit;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddressDelivery(): ?string
    {
        return $this->addressDelivery;
    }

    /**
     * @param string|null $addressDelivery
     * @return OrderDto
     */
    public function setAddressDelivery(?string $addressDelivery): OrderDto
    {
        $this->addressDelivery = $addressDelivery;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAcceptCode(): ?string
    {
        return $this->acceptCode;
    }

    /**
     * @param string|null $acceptCode
     * @return OrderDto
     */
    public function setAcceptCode(?string $acceptCode): OrderDto
    {
        $this->acceptCode = $acceptCode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSid(): ?string
    {
        return $this->sid;
    }

    /**
     * @param string|null $sid
     * @return OrderDto
     */
    public function setSid(?string $sid): OrderDto
    {
        $this->sid = $sid;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDateOrderExecution(): ?string
    {
        return $this->dateOrderExecution;
    }

    /**
     * @param string|null $dateOrderExecution
     * @return OrderDto
     */
    public function setDateOrderExecution(?string $dateOrderExecution): OrderDto
    {
        $this->dateOrderExecution = $dateOrderExecution;
        return $this;
    }

}