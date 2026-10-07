<?php
declare(strict_types=1);

/**
 * Domain helpers: currency conversion, AI-style categorisation,
 * duplicate detection and simulated OCR.
 */
final class Services
{
    /** Home-currency (EUR) value of 1 unit of the foreign currency. */
    public static function rateFor(string $currency): float
    {
        $currency = strtoupper($currency);
        if ($currency === 'EUR') {
            return 1.0;
        }
        $stmt = db()->prepare('SELECT rate FROM exchange_rates WHERE currency = ?');
        $stmt->execute([$currency]);
        $row = $stmt->fetch();
        return $row ? (float)$row['rate'] : 1.0;
    }

    public static function toHome(float $amount, string $currency): float
    {
        return round($amount * self::rateFor($currency), 2);
    }

    /** Units of `$to` obtained for one unit of `$from`. */
    public static function exchangeRate(string $from, string $to): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);
        if ($from === $to) {
            return 1.0;
        }
        $fromRate = self::rateFor($from);
        $toRate = self::rateFor($to);
        if ($toRate == 0) {
            return 1.0;
        }
        return round($fromRate / $toRate, 6);
    }

    public static function toCurrency(float $amount, string $from, string $to): float
    {
        return round($amount * self::exchangeRate($from, $to), 2);
    }

    /** Predict a category id from historical data for a vendor (naive "AI"). */
    public static function suggestCategory(string $vendor): ?int
    {
        $vendor = trim($vendor);
        if ($vendor === '') {
            return null;
        }
        $stmt = db()->prepare(
            'SELECT category_id, COUNT(*) AS n FROM documents
             WHERE vendor = ? AND category_id IS NOT NULL
             GROUP BY category_id ORDER BY n DESC LIMIT 1'
        );
        $stmt->execute([$vendor]);
        $row = $stmt->fetch();
        return $row ? (int)$row['category_id'] : null;
    }

    public static function docHash(string $vendor, float $amount, string $date): string
    {
        return md5(strtolower(trim($vendor)) . '|' . number_format($amount, 2, '.', '') . '|' . $date);
    }

    /** Return the id of an existing document with the same hash, if any. */
    public static function detectDuplicate(string $hash, ?int $excludeId = null): ?int
    {
        if ($excludeId) {
            $stmt = db()->prepare('SELECT id FROM documents WHERE hash = ? AND id != ? LIMIT 1');
            $stmt->execute([$hash, $excludeId]);
        } else {
            $stmt = db()->prepare('SELECT id FROM documents WHERE hash = ? LIMIT 1');
            $stmt->execute([$hash]);
        }
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    /** Simulated OCR result for a vendor string. */
    public static function robotDigitize(string $vendor): array
    {
        return [
            'doc_number' => 'ROBO-' . strtoupper(substr(md5($vendor . microtime()), 0, 8)),
            'is_verified' => 1,
        ];
    }
}
