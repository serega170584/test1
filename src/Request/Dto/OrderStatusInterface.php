<?php

namespace App\Request\Dto;

/**
 * Интерфейс статусов заказов ИМ.
 */
interface OrderStatusInterface
{
    /** Оформлен */
//    public const STATUS_NEW = 'N';

    /** Подтвержден */
    public const STATUS_CREATED = 'O';

    /** Требует подтверждения */
    public const STATUS_CONFIRMATION = 'M';

    /** Аннулирован\Отказ\Отменен */
    public const STATUS_CANCELLED = 'C';

    /** Частично собран */
//    public const STATUS_PART_ASSEMBLED = 'P';

    /** Частичный отказ в резерве */
//    public const STATUS_PART_CANCELED = 'E';

    /** Собирается */
    public const STATUS_ASSEMBLING = 'S';

    /** Собран */
//    public const STATUS_ASSEMBLED = 'A';

    /** В пути */
//    public const STATUS_PROCESSING = 'G';

    /** К выдаче */
    public const STATUS_LET_OUT = 'L';

    /** Частично к выдаче */
//    public const STATUS_PART_LET_OUT = 'T';

    /** Выкуплен */
    public const STATUS_FINISHED = 'F';

    /** Не выкуплен */
    public const STATUS_NOT_REDEEMED = 'R';

    /** Ожидание МДЛП */
    public const STATUS_WAIT_MDLP = 'W';

    /** Готов к передаче курьеру */
    public const STATUS_READY_TO_COURIER = 'B';

    /** Передан курьеру */
    public const STATUS_TRANSFERRED_TO_COURIER = 'H';

    /** Ожидание возврата */
    public const STATUS_WAITING_OF_RETURN = 'I';

    /** Утерян */
    public const STATUS_LOST_BY_COURIER = 'J';

    /** Невыкуп принят */
    public const STATUS_NON_PURCHASE_ACCEPTED = 'K';

    /** Невыкуп принят частично */
    public const STATUS_NON_PURCHASE_PARTIALLY_ACCEPTED = 'D';

    /** Ожидание курьера */
    public const STATUS_WAITING_OF_COURIER = 'Q';

    /** Рецепт погашен */
    public const STATUS_RECIPE_COMPLETED = 'U';

    /** На торгах */
    public const STATUS_ON_TRADES = 'V';

    public const FINISHED_STATUSES = [
        self::STATUS_FINISHED,
        self::STATUS_CANCELLED,
        self::STATUS_LOST_BY_COURIER,
        self::STATUS_NON_PURCHASE_ACCEPTED,
    ];
}
