<?php

namespace App\Tests\ImportRule;

use App\DTO\ProductDto;
use App\ImportRule\MaxPriceRule;
use App\ImportRule\MinStockAndPriceRule;
use PHPUnit\Framework\TestCase;

class ImportRulesTest extends TestCase
{
    public function testLowStockAndLowPriceRuleBlocksProduct(): void
    {
        $rule = new MinStockAndPriceRule();
        $dto = new ProductDto(
            code: 'P0001',
            name: 'Cheap Low Stock',
            description: 'Some description',
            stock: 5,
            price: '4.99',
            discontinued: true,
        );

        $this->assertFalse(
            $rule->isAllowed($dto),
            'Product with price < $5 and stock < 10 should be blocked'
        );
    }

    public function testLowStockAndLowPriceRuleAllowsProduct(): void
    {
        $rule = new MinStockAndPriceRule();
        $dto = new ProductDto(
            code: 'P0002',
            name: 'OK Product',
            description: 'Some description',
            stock: 11,
            price: '4.99',
            discontinued: true,
        );

        $this->assertTrue(
            $rule->isAllowed($dto),
            'Product should be allowed if stock >= 10 even with low price'
        );
    }

    public function testMaxPriceRuleBlocksHighPrice(): void
    {
        $rule = new MaxPriceRule();
        $dto = new ProductDto(
            code: 'P0003',
            name: 'Expensive Product',
            description: 'Some description',
            stock: 15,
            price: '1000.01',
            discontinued: false,
        );

        $this->assertFalse(
            $rule->isAllowed($dto),
            'Product with price > $1000 should be blocked'
        );
    }

    public function testMaxPriceRuleAllowsValidPrice(): void
    {
        $rule = new MaxPriceRule();
        $dto = new ProductDto(
            code: 'P0004',
            name: 'Normal Product',
            description: 'Some description',
            stock: 15,
            price: '500',
            discontinued: false,
        );

        $this->assertTrue(
            $rule->isAllowed($dto),
            'Product with valid price should be allowed'
        );
    }
}
