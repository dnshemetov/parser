<?php

namespace App\ImportRule;

use App\Dto\ProductDto;

class MinStockAndPriceRule implements ImportRuleInterface
{
    public function isAllowed(ProductDto $dto): bool
    {
        // Skip if price < 5 AND stock < 10
        return !($dto->price < 5 && $dto->stock < 10);
    }
}
