<?php

// Place the polyfill in an explicit global namespace block so the
// following "namespace Illuminate\Support;" declaration remains the
// first non-declarative statement for that namespace (and to avoid
// "namespace must be the first statement" PHP errors).
namespace {
    if (! class_exists('NumberFormatter')) {
        class NumberFormatter
        {
            public const DECIMAL = 0;
            public const SPELLOUT = 1;
            public const ORDINAL = 2;
            public const PERCENT = 3;
            public const CURRENCY = 4;

            public const MAX_FRACTION_DIGITS = 1001;
            public const FRACTION_DIGITS = 1000;

            public const DEFAULT_RULESET = 'default';

            protected $locale;
            protected $style;
            protected $fractionDigits = 2;

            public function __construct($locale = 'en', $style = self::DECIMAL)
            {
                $this->locale = $locale;
                $this->style = $style;
            }

            public function setAttribute($attr, $value)
            {
                if ($attr === self::MAX_FRACTION_DIGITS || $attr === self::FRACTION_DIGITS) {
                    $this->fractionDigits = (int) $value;
                }
                return true;
            }

            public function setTextAttribute($attr, $value)
            {
                return true;
            }

            public function format($number)
            {
                return number_format((float) $number, $this->fractionDigits, '.', ',');
            }

            public function parse($string, $type = null)
            {
                $normalized = str_replace([' ', ','], ['', ''], $string);
                $normalized = str_replace(',', '.', $normalized);

                if ($type === null) {
                    return is_numeric($normalized) ? (float) $normalized : false;
                }

                return is_numeric($normalized) ? $normalized + 0 : false;
            }

            public function formatCurrency($number, $currency)
            {
                return $this->format($number) . ' ' . $currency;
            }
        }
    }
}

// Provide a safe fallback for Illuminate\Support\Number when PHP's intl extension is not available.
// This file is intentionally simple and provides basic, locale-agnostic behavior so the app
// doesn't throw RuntimeExceptions. It is NOT a full replacement for the intl behavior.

namespace Illuminate\Support {

    if (! class_exists(Number::class)) {
        class Number
        {
        protected static $locale = 'en';
        protected static $currency = 'USD';

        public static function format(int|float $number, ?int $precision = null, ?int $maxPrecision = null, ?string $locale = null)
        {
            // Use provided precision if available, otherwise default to 0 or guess decimals from float
            if (! is_null($maxPrecision)) {
                $decimals = $maxPrecision;
            } elseif (! is_null($precision)) {
                $decimals = $precision;
            } else {
                // Default: if number has fraction, show 2 decimals, otherwise 0
                $decimals = (floor($number) != $number) ? 2 : 0;
            }

            return number_format($number, $decimals, '.', ',');
        }

        public static function parse(string $string, $type = null, ?string $locale = null): int|float|false
        {
            // Remove common thousand separators and convert comma decimals to dot
            $normalized = str_replace([',', ' '], ['', ''], $string);
            $normalized = str_replace(',', '.', $normalized);

            if ($type === null || $type === \NumberFormatter::TYPE_DOUBLE) {
                return is_numeric($normalized) ? (float) $normalized : false;
            }

            return is_numeric($normalized) ? (int) $normalized : false;
        }

        public static function parseInt(string $string, ?string $locale = null): int|false
        {
            $v = self::parse($string, \NumberFormatter::TYPE_INT32, $locale);
            return ($v === false) ? false : (int) $v;
        }

        public static function parseFloat(string $string, ?string $locale = null): float|false
        {
            $v = self::parse($string, \NumberFormatter::TYPE_DOUBLE, $locale);
            return ($v === false) ? false : (float) $v;
        }

        public static function fileSize(int|float $bytes, int $precision = 0, ?int $maxPrecision = null)
        {
            $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
            $unitCount = count($units);

            $i = 0;
            for ($i = 0; ($bytes / 1024) > 0.9 && ($i < $unitCount - 1); $i++) {
                $bytes /= 1024;
            }

            return sprintf('%s %s', static::format($bytes, $precision, $maxPrecision), $units[$i]);
        }

        // Minimal implementations for methods referenced by framework or packages.
        public static function percentage(int|float $number, int $precision = 0, ?int $maxPrecision = null, ?string $locale = null)
        {
            return static::format($number / 100, $precision, $maxPrecision, $locale);
        }

        public static function currency(int|float $number, string $in = '', ?string $locale = null, ?int $precision = null)
        {
            $formatted = static::format($number, $precision ?? 2, null, $locale);
            $currency = $in ?: static::$currency;
            return $formatted . ' ' . $currency;
        }

        public static function abbreviate(int|float $number, int $precision = 0, ?int $maxPrecision = null)
        {
            return static::forHumans($number, $precision, $maxPrecision, abbreviate: true);
        }

        public static function forHumans(int|float $number, int $precision = 0, ?int $maxPrecision = null, bool $abbreviate = false)
        {
            return static::summarize($number, $precision, $maxPrecision, $abbreviate ? [3 => 'K', 6 => 'M', 9 => 'B', 12 => 'T', 15 => 'Q'] : [3 => ' thousand', 6 => ' million', 9 => ' billion', 12 => ' trillion', 15 => ' quadrillion']);
        }

        protected static function summarize(int|float $number, int $precision = 0, ?int $maxPrecision = null, array $units = [])
        {
            if (empty($units)) {
                $units = [3 => 'K', 6 => 'M', 9 => 'B', 12 => 'T', 15 => 'Q'];
            }

            if (floatval($number) === 0.0) {
                return $precision > 0 ? static::format(0, $precision, $maxPrecision) : '0';
            }

            if ($number < 0) {
                return sprintf('-%s', static::summarize(abs($number), $precision, $maxPrecision, $units));
            }

            if ($number >= 1e15) {
                return sprintf('%s'.end($units), static::summarize($number / 1e15, $precision, $maxPrecision, $units));
            }

            $numberExponent = floor(log10($number));
            $displayExponent = $numberExponent - ($numberExponent % 3);
            $number /= pow(10, $displayExponent);

            return trim(sprintf('%s%s', static::format($number, $precision, $maxPrecision), $units[$displayExponent] ?? ''));
        }

        public static function clamp(int|float $number, int|float $min, int|float $max)
        {
            return min(max($number, $min), $max);
        }

        public static function pairs(int|float $to, int|float $by, int|float $start = 0, int|float $offset = 1)
        {
            $output = [];

            for ($lower = $start; $lower < $to; $lower += $by) {
                $upper = $lower + $by - $offset;

                if ($upper > $to) {
                    $upper = $to;
                }

                $output[] = [$lower, $upper];
            }

            return $output;
        }

        public static function trim(int|float $number)
        {
            return json_decode(json_encode($number));
        }

        public static function withLocale(string $locale, callable $callback)
        {
            $previousLocale = static::$locale;
            static::useLocale($locale);

            try {
                return $callback();
            } finally {
                static::useLocale($previousLocale);
            }
        }

        public static function withCurrency(string $currency, callable $callback)
        {
            $previousCurrency = static::$currency;
            static::useCurrency($currency);

            try {
                return $callback();
            } finally {
                static::useCurrency($previousCurrency);
            }
        }

        public static function useLocale(string $locale)
        {
            static::$locale = $locale;
        }

        public static function useCurrency(string $currency)
        {
            static::$currency = $currency;
        }

        public static function defaultLocale()
        {
            return static::$locale;
        }

        public static function defaultCurrency()
        {
            return static::$currency;
        }

        protected static function ensureIntlExtensionIsInstalled()
        {
            // No-op fallback: if intl is missing, just proceed with the fallback behavior.
            return;
        }
    }
}
}
