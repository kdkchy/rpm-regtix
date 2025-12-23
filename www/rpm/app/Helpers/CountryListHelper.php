<?php

namespace App\Helpers;

class CountryListHelper
{
    public static function get(string $locale = 'en', bool $flat = false): array
    {
        $path = base_path("vendor/umpirsky/country-list/data/{$locale}/country.php");
        $countries = file_exists($path) ? include $path : [];

        if ($flat) {
            return collect($countries)
                ->mapWithKeys(fn($name) => [$name => $name])
                ->toArray();
        }

        return $countries;
    }

    public static function getName(string $code, string $locale = 'en'): ?string
    {
        $countries = self::get($locale);
        return $countries[$code] ?? null;
    }
}
