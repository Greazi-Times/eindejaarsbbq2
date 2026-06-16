<?php

namespace App\Support;

final class EducationOptions
{
    public const OTHER = 'anders';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'mechatronica' => 'Mechatronica',
            'werktuigbouwkunde' => 'Werktuigbouwkunde',
            'technische-informatica' => '(Technische) Informatica',
            'elektrotechniek' => 'Elektrotechniek',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function formOptions(): array
    {
        return [
            ...self::options(),
            self::OTHER => 'Anders',
        ];
    }

    public static function label(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::formOptions()[$value] ?? $value;
    }
}
