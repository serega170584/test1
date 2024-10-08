<?php

namespace App\Request\Dto\PROVIDER;

/**
 * Статус заказа.
 */
interface OrderStatusInterface
{
    // Статусы УАС

    /** Создан */
    public const STATUS_CREATED = 1;

    /** Отменен */
    public const STATUS_CANCELED = 2;

    /** Готов к выдаче */
    public const STATUS_READY = 3;

    /** Частично готов к выдаче */
    public const STATUS_PART_READY = 4;

    /** В работе (собирается) */
    public const STATUS_ASSEMBLING = 5;

    /** Выполнен */
    public const STATUS_COMPLETED = 6;

    /** Ожидание курьера  */
    public const STATUS_WAITING_OF_COURIER = 8;

    /** Выдан курьеру  */
    public const STATUS_TRANSFERRED_TO_COURIER = 9;

    /** Ожидание возврата */
    public const STATUS_WAITING_OF_RETURN = 10;

    /** Утерян */
    public const STATUS_LOST_BY_COURIER = 13;

    /** Невыкуп принят */
    public const STATUS_NON_PURCHASE_ACCEPTED = 11;

    /** Невыкуп принят частично */
    public const STATUS_NON_PURCHASE_PARTIALLY_ACCEPTED = 12;

    /** Ожидание МДЛП */
    public const STATUS_WAIT_MDLP = 16;

    /** Аннулирован */
    public const STATUS_NULLIFIED = 17;

    /** Рецепт погашен */
    public const STATUS_RECIPE_COMPLETED = 15;

    /** На торгах */
    public const STATUS_ON_TRADES = 18;

    /**
     * Возвращает цифровое значение статуса заказа (УАС).
     */
    public function getStatus(): ?int;

    public function setStatus(?int $status): self;

    /**
     * Возвращает доступные статусы заказов в УАС.
     */
    public static function getStatuses(): array;

    public function isStatusPartReady(): bool;
}
