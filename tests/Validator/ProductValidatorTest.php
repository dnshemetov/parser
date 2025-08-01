<?php

namespace App\Tests\Validator;

use App\DTO\ProductDto;
use App\Validator\ProductValidator;
use PHPUnit\Framework\TestCase;

class ProductValidatorTest extends TestCase
{
    private ProductValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ProductValidator();
    }

    public function testValidProduct(): void
    {
        $dto = new ProductDto(
            code: 'P0001',
            name: 'Valid Product',
            description: 'Valid description',
            stock: 10,
            price: '25.50',
            discontinued: true
        );

        $errors = $this->validator->validateDto($dto);

        $this->assertEmpty($errors, 'Valid product should not return validation errors');
    }

    public function testMissingCode(): void
    {
        $dto = new ProductDto(
            code: '',
            name: 'Product Without Code',
            description: 'Some description',
            stock: 10,
            price: '50',
            discontinued: true

        );

        $errors = $this->validator->validateDto($dto);

        $this->assertNotEmpty($errors, 'Product without code should return errors');
        $this->assertContains('Product Code is missing', $errors);
    }

    public function testInvalidPrice(): void
    {
        $dto = new ProductDto(
            code: 'P0003',
            name: 'Invalid Price Product',
            description: 'Some description',
            stock: 5,
            price: null,
            discontinued: true
        );

        $errors = $this->validator->validateDto($dto);

        $this->assertNotEmpty($errors, 'Product with invalid price should return errors');
        $this->assertContains('Price is missing or invalid', $errors);
    }
}
