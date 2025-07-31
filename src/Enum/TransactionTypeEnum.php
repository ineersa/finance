<?php

declare(strict_types=1);

namespace App\Enum;

enum TransactionTypeEnum: string
{
    case Credit = 'credit';
    case Debit = 'debit';
}
