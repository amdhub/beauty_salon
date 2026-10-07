<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Mpesa = 'mpesa';
    case AirtelMoney = 'airtel_money';
    case OrangeMoney = 'orange_money';
    case Bank = 'bank';
    case Card = 'card';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Espèces',self::Mpesa => 'M-Pesa',self::AirtelMoney => 'Airtel Money',self::OrangeMoney => 'Orange Money',self::Bank => 'Banque',self::Card => 'Carte',self::Other => 'Autre',
        };
    }
}
