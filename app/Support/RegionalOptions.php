<?php

namespace App\Support;

final class RegionalOptions
{
    /**
     * @return list<string>
     */
    public static function timezones(): array
    {
        return [
            'UTC',
            'Asia/Kolkata',
            'Asia/Dubai',
            'Asia/Singapore',
            'Asia/Riyadh',
            'Europe/London',
            'Europe/Berlin',
            'America/New_York',
            'America/Chicago',
            'America/Denver',
            'America/Los_Angeles',
            'Australia/Sydney',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function currencies(): array
    {
        return [
            'INR' => 'Indian Rupee',
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'AED' => 'UAE Dirham',
            'SAR' => 'Saudi Riyal',
            'SGD' => 'Singapore Dollar',
            'AUD' => 'Australian Dollar',
            'CAD' => 'Canadian Dollar',
            'ZAR' => 'South African Rand',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function locales(): array
    {
        return [
            'en' => 'English',
            'en-IN' => 'English (India)',
            'en-US' => 'English (United States)',
            'en-GB' => 'English (United Kingdom)',
            'hi' => 'Hindi',
            'mr' => 'Marathi',
            'ar' => 'Arabic',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function countries(): array
    {
        return [
            'IN' => 'India',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia',
            'SG' => 'Singapore',
            'AU' => 'Australia',
            'CA' => 'Canada',
            'DE' => 'Germany',
            'ZA' => 'South Africa',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function weekStartDays(): array
    {
        return [
            'monday' => 'Monday',
            'sunday' => 'Sunday',
            'saturday' => 'Saturday',
        ];
    }

    /**
     * @return list<string>
     */
    public static function definitionOptions(string $key): array
    {
        return match ($key) {
            'tenant.regional.timezone', 'branch.regional.timezone' => self::timezones(),
            'tenant.regional.currency', 'branch.regional.currency' => array_keys(self::currencies()),
            'tenant.regional.locale' => array_keys(self::locales()),
            'tenant.regional.week_start_day', 'branch.regional.week_start_day' => array_keys(self::weekStartDays()),
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function definitionOptionLabels(string $key): array
    {
        return match ($key) {
            'tenant.regional.currency', 'branch.regional.currency' => self::currencyLabels(),
            'tenant.regional.locale' => self::locales(),
            'tenant.regional.week_start_day', 'branch.regional.week_start_day' => self::weekStartDays(),
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    protected static function currencyLabels(): array
    {
        $labels = [];
        foreach (self::currencies() as $code => $label) {
            $labels[$code] = $code . ' - ' . $label;
        }

        return $labels;
    }
}
