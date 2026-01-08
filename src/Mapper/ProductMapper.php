<?php

namespace App\Mapper;

use App\Dto\ProductDto;

/**
 * Responsible for mapping CSV records into ProductDto instances.
 */
class ProductMapper
{
    /**
     * Convert one CSV record into ProductDto.
     *
     * @param array $record
     * @return ProductDto
     */
    public static function fromRecord(array $record): ProductDto
    {
        return new ProductDto(
            code: self::normalizeText($record['Product Code'] ?? ''),
            name: self::normalizeText($record['Product Name'] ?? ''),
            description: self::normalizeText($record['Product Description'] ?? ''),
            stock: self::parseStock($record['Stock'] ?? null),
            price: self::normalizePrice($record['Cost in GBP'] ?? null),
            discontinued: self::parseDiscontinued($record['Discontinued'] ?? '')
        );
    }

    /**
     * Ensure text is properly trimmed and re-encoded to UTF-8 if needed.
     *
     * @param string $text
     * @return string
     */
    private static function normalizeText(string $text): string
    {
        $text = trim($text);

        // If text is not valid UTF-8, try converting from Windows-1252
        if (!mb_check_encoding($text, 'UTF-8')) {
            $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }

        // Replace problematic symbols with proper ones
        $map = [
            "\xC2\x91" => "'",  // ‘
            "\xC2\x92" => "'",  // ’
            "\xC2\x93" => '"',  // “
            "\xC2\x94" => '"',  // ”
            "\xC2\x96" => '-',  // –
            "\xC2\x97" => '-',  // —
            "\xC2\xA0" => ' ',  // non-breaking space
        ];

        return strtr($text, $map);
    }

    /**
     * Normalize and validate price value.
     *
     * @param string|null $price
     * @return string|null
     */
    private static function normalizePrice(?string $price): ?string
    {
        if ($price === null || trim($price) === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9.,]/', '', $price);
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized)
            ? number_format((float)$normalized, 2, '.', '')
            : null;
    }

    /**
     * Parse stock column.
     *
     * @param string|null $stock
     * @return int|null
     */
    private static function parseStock(?string $stock): ?int
    {
        return ($stock !== null && trim($stock) !== '') ? (int)$stock : null;
    }

    /**
     * Parse discontinued column.
     *
     * @param string $value
     * @return bool
     */
    private static function parseDiscontinued(string $value): bool
    {
        return strtolower(trim($value)) === 'yes';
    }
}
