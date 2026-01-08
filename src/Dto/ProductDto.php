<?php

namespace App\Dto;

/**
 * Data Transfer Object for product import data.
 */
class ProductDto
{
    public function __construct(
        public readonly string  $code,
        public readonly string  $name,
        public readonly string  $description,
        public readonly ?int    $stock,
        public readonly ?string $price,
        public readonly bool    $discontinued
    )
    {
    }
}
