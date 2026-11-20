<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case TRANSFER_BANK = 'Transfer Bank';
    case TUNAI         = 'Tunai';
    case QRIS          = 'QRIS';

    public function label(): string
    {
        return $this->value;
    }
}
