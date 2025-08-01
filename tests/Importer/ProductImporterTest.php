<?php

namespace App\Tests\Importer;

use App\Dto\ProductDto;
use App\Dto\ImportResult;
use App\Service\ProductImporter;
use App\Validator\ProductValidator;
use App\ImportRule\ImportRuleInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class ProductImporterTest extends TestCase
{
    private EntityManagerInterface $entityManagerMock;
    private ProductValidator $validatorMock;
    private KernelInterface $kernelMock;
    private ImportRuleInterface $ruleMock;

    protected function setUp(): void
    {
        // Create mocks for dependencies
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->validatorMock = $this->createMock(ProductValidator::class);
        $this->kernelMock = $this->createMock(KernelInterface::class);
        $this->ruleMock = $this->createMock(ImportRuleInterface::class);

        // Stub getProjectDir() to a temp path for logs
        $this->kernelMock->method('getProjectDir')->willReturn('/tmp/project');

        // Make sure log directory exists to avoid write errors during tests
        $logDir = '/tmp/project/var/log';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }

    public function testImportProcessesValidAndInvalidRecords(): void
    {
        // Create a partial mock of ProductImporter to override readCsv() and save()
        $importer = $this->getMockBuilder(ProductImporter::class)
            ->setConstructorArgs([
                $this->entityManagerMock,
                $this->validatorMock,
                [$this->ruleMock],
                $this->kernelMock
            ])
            ->onlyMethods(['readCsv', 'save'])
            ->getMock();

        // Define a valid product DTO
        $validProduct = new ProductDto(
            code: 'P0001',
            name: 'Valid Product',
            description: 'A valid product description',
            stock: 20,
            price: '100',
            discontinued: false
        );

        // Define an invalid product DTO (missing required fields)
        $invalidProduct = new ProductDto(
            code: '',
            name: '',
            description: '',
            stock: null,
            price: null,
            discontinued: false
        );

        // Mock readCsv() to yield one valid and one invalid product DTO
        $importer->method('readCsv')->willReturn((function () use ($validProduct, $invalidProduct) {
            yield $validProduct;
            yield $invalidProduct;
        })());

        // Validator returns empty error array for valid product
        // and some errors for invalid product
        $this->validatorMock->method('validateDto')
            ->willReturnMap([
                [$validProduct, []],
                [$invalidProduct, ['Product Code is missing', 'Product Name is missing']]
            ]);

        // The import rule allows all products (returns true)
        $this->ruleMock->method('isAllowed')->willReturn(true);

        // Expect save() to be called once with the valid product and return false (inserted)
        $importer->expects($this->once())
            ->method('save')
            ->with($validProduct)
            ->willReturn(false);

        // Run import with isTest = false to actually call save()
        $result = $importer->import('dummy.csv', false);

        // Assertions to verify correct counters in ImportResult
        $this->assertInstanceOf(ImportResult::class, $result);
        $this->assertSame(2, $result->getProcessed(), 'Two records should be processed');
        $this->assertSame(1, $result->getSuccess(), 'One record should be successful');
        $this->assertSame(1, $result->getSkipped(), 'One record should be skipped due to validation');
        $this->assertSame(0, $result->getUpdated(), 'No records should be updated');
    }
}
