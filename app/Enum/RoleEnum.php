<?php

namespace App\Enum;

enum RoleEnum: string
{
    case Admin = 'admin';
    case Staff = 'staff';
    case Cashier = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Staff => 'Staff',
            self::Cashier => 'Cashier',
        };
    }
}
