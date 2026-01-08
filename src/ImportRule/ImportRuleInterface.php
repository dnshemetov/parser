<?php

namespace App\ImportRule;

use App\Dto\ProductDto;

/**
 * Defines a contract for import rules applied to each record.
 */
interface ImportRuleInterface
{
    /**
     * Checks whether the given record is allowed to be imported.
     */
    public function isAllowed(ProductDto $dto): bool;
}
