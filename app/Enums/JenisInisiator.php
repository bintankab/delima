<?php

namespace App\Enums;

enum JenisInisiator: string
{
    case Bupati = 'bupati';
    case DPRD = 'dprd';
    case OPD = 'opd';
    case ASN = 'asn';
    case Masyarakat = 'masyarakat';

    public function label(): string
    {
        return match ($this) {
            self::Bupati => 'Bupati',
            self::DPRD => 'DPRD',
            self::OPD => 'OPD',
            self::ASN => 'ASN',
            self::Masyarakat => 'Masyarakat',
        };
    }

    /**
     * Map a Spatie role name to its corresponding inisiator type.
     * Returns null when the role has no fixed inisiator (e.g. super-admin, reviewer),
     * meaning the caller must let the user pick one manually.
     */
    public static function fromRole(string $role): ?self
    {
        return match ($role) {
            'bupati' => self::Bupati,
            'dprd' => self::DPRD,
            'admin-opd' => self::OPD,
            'asn' => self::ASN,
            'publik' => self::Masyarakat,
            default => null,
        };
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['id' => $case->value, 'name' => $case->label()],
            self::cases(),
        );
    }
}
