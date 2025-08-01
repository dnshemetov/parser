<?php

namespace App\Tests\Mapper;

use App\Mapper\ProductMapper;
use App\Dto\ProductDto;
use PHPUnit\Framework\TestCase;

class ProductMapperTest extends TestCase
{
    public function testFromRecordMapsAndCleansDataCorrectly(): void
    {
        $record = [
            'Product Code' => ' P0001 ',
            'Product Name' => ' 32” TV ',
            // description в Windows-1252 для проверки перекодировки
            'Product Description' => iconv('UTF-8', 'Windows-1252', '32” Tv'),
            'Stock' => ' 10 ',
            'Cost in GBP' => ' £199.99 ',
            'Discontinued' => ' Yes '
        ];

        /** @var ProductDto $dto */
        $dto = ProductMapper::fromRecord($record);

        // Проверяем все поля
        $this->assertInstanceOf(ProductDto::class, $dto);

        $this->assertSame('P0001', $dto->code, 'Code should be trimmed');
        $this->assertSame('32” TV', $dto->name, 'Name should be trimmed and preserved');
        $this->assertSame('32” Tv', $dto->description, 'Description should be re-encoded to UTF-8');
        $this->assertSame(10, $dto->stock, 'Stock should be integer');
        $this->assertSame('199.99', $dto->price, 'Price should be normalized and formatted');
        $this->assertTrue($dto->discontinued, 'Discontinued should be parsed as boolean');
    }

    public function testFromRecordHandlesEmptyValues(): void
    {
        $record = [
            'Product Code' => '',
            'Product Name' => '',
            'Product Description' => '',
            'Stock' => '',
            'Cost in GBP' => '',
            'Discontinued' => 'No'
        ];

        $dto = ProductMapper::fromRecord($record);

        $this->assertSame('', $dto->code, 'Empty code should remain empty');
        $this->assertSame('', $dto->name, 'Empty name should remain empty');
        $this->assertSame('', $dto->description, 'Empty description should remain empty');
        $this->assertNull($dto->stock, 'Empty stock should be null');
        $this->assertNull($dto->price, 'Empty price should be null');
        $this->assertFalse($dto->discontinued, 'Discontinued should be false if not "yes"');
    }
}
