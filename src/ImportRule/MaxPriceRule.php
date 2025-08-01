<?php

namespace App\ImportRule;

use App\Dto\ProductDto;

class MaxPriceRule implements ImportRuleInterface
{
    public function isAllowed(ProductDto $dto): bool
    {
        // Skip if price > 1000
        return $dto->price <= 1000;
    }
}
