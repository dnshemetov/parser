<?php

namespace App\Tests\Importer;

use App\DataSource\CsvReader;
use App\Dto\ImportResult;
use App\Dto\ProductDto;
use App\ImportRule\ImportRuleInterface;
use App\Service\ProductImporter;
use App\Service\ProductService;
use App\Validator\ProductValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class ProductImporterTest extends TestCase
{
    private KernelInterface $kernelMock;

    protected function setUp(): void
    {
        // Kernel mock to provide a temp project dir for logs
        $this->kernelMock = $this->createMock(KernelInterface::class);
        $this->kernelMock->method('getProjectDir')->willReturn('/tmp/project');

        // Ensure /tmp/project/var/log exists to avoid file_put_contents warnings
        $logDir = '/tmp/project/var/log';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
    }

    public function testImportProcessesValidAndInvalidRecords(): void
    {
        $filePath = 'dummy.csv';

        // Arrange DTOs
        $validDto = new ProductDto(
            code: 'P0001',
            name: 'Valid Product',
            description: 'A valid product description',
            stock: 20,
            price: '100.00',
            discontinued: false
        );

        $invalidDto = new ProductDto(
            code: '',
            name: '',
            description: '',
            stock: null,
            price: null,
            discontinued: false
        );

        // Mocks
        $csvReader = $this->createMock(CsvReader::class);
        // CsvReader must yield two DTOs
        $csvReader->expects($this->once())
            ->method('readFile')
            ->with($filePath)
            ->willReturn((function () use ($validDto, $invalidDto) {
                yield $validDto;
                yield $invalidDto;
            })());

        $validator = $this->createMock(ProductValidator::class);
        // No errors for valid, errors for invalid
        $validator->method('validateDto')
            ->willReturnMap([
                [$validDto, []],
                [$invalidDto, ['Product Code is missing']],
            ]);

        $rule = $this->createMock(ImportRuleInterface::class);
        // Rules allow everything in this test
        $rule->method('isAllowed')->willReturn(true);

        $productService = $this->createMock(ProductService::class);
        // Save must be called once for the valid DTO. Return false => treated as inserted (not updated).
        $productService->expects($this->once())
            ->method('save')
            ->with($validDto)
            ->willReturn(false);

        // System under test (constructor order matters!)
        $importer = new ProductImporter(
            productService: $productService,
            validator:      $validator,
            rules:          [$rule],
            kernel:         $this->kernelMock,
            csvReader:      $csvReader
        );

        // Act
        $result = $importer->import($filePath, false);

        // Assert
        $this->assertInstanceOf(ImportResult::class, $result);
        $this->assertSame(2, $result->getProcessed(), 'Two records should be processed');
        $this->assertSame(1, $result->getSuccess(),   'One record should be successful');
        $this->assertSame(1, $result->getSkipped(),   'One record should be skipped due to validation');
        $this->assertSame(0, $result->getUpdated(),   'No records should be updated');
    }

    public function testImportBlockedByRule(): void
    {
        $filePath = 'dummy.csv';

        $dto = new ProductDto(
            code: 'P0099',
            name: 'Blocked Product',
            description: 'Should be blocked by rule',
            stock: 5,
            price: '4.99',
            discontinued: false
        );

        $csvReader = $this->createMock(CsvReader::class);
        $csvReader->expects($this->once())
            ->method('readFile')
            ->with($filePath)
            ->willReturn((function () use ($dto) {
                yield $dto;
            })());

        $validator = $this->createMock(ProductValidator::class);
        // No validation errors => rules will decide
        $validator->method('validateDto')->with($dto)->willReturn([]);

        $rule = $this->createMock(ImportRuleInterface::class);
        // Simulate rule blocking this DTO
        $rule->expects($this->once())
            ->method('isAllowed')
            ->with($dto)
            ->willReturn(false);

        $productService = $this->createMock(ProductService::class);
        // Must not be called because rule blocks the item
        $productService->expects($this->never())->method('save');

        $importer = new ProductImporter(
            productService: $productService,
            validator:      $validator,
            rules:          [$rule],
            kernel:         $this->kernelMock,
            csvReader:      $csvReader
        );

        $result = $importer->import($filePath, false);

        $this->assertSame(1, $result->getProcessed());
        $this->assertSame(0, $result->getSuccess());
        $this->assertSame(1, $result->getSkipped());
        $this->assertSame(0, $result->getUpdated());
    }
}
