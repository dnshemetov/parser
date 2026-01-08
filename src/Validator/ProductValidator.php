<?php

namespace App\Validator;

use App\Dto\ProductDto;

/**
 * Responsible for validating CSV headers and product data rows.
 */
class ProductValidator
{
    /**
     * Validate that CSV headers match expected format.
     *
     * @param array $headers CSV headers
     * @throws \RuntimeException if headers do not match
     */

    /**
     * Validate a single ProductDto instance.
     *
     * @param ProductDto $dto
     * @return string[] list of validation error messages
     */
    public function validateDto(ProductDto $dto): array
    {
        $errors = [];

        if ($dto->code === '') {
            $errors[] = "Product Code is missing";
        }
        if ($dto->name === '') {
            $errors[] = "Product Name is missing";
        }
        if ($dto->description === '') {
            $errors[] = "Product Description is missing";
        }
        if ($dto->stock === null) {
            $errors[] = "Stock is missing or invalid";
        }
        if ($dto->price === null) {
            $errors[] = "Price is missing or invalid";
        }

        return $errors;
    }
}
