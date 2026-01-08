<?php

namespace App\Service;

use App\DataSource\CsvReader;
use App\Dto\ImportResult;
use App\Dto\ProductDto;
use App\Mapper\ProductMapper;
use App\Validator\ProductValidator;
use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use League\Csv\UnavailableStream;
use RuntimeException;
use Symfony\Component\HttpKernel\KernelInterface;
use League\Csv\Reader;

class ProductImporter
{
    private array $duplicates = [];
    private string $errorLogPath;

    public function __construct(
        private readonly ProductService $productService,
        private readonly ProductValidator       $validator,
        private readonly iterable               $rules,
        private readonly KernelInterface        $kernel,
        private readonly CsvReader              $csvReader,
    )
    {
        $this->errorLogPath = sprintf(
            '%s/var/log/import_errors_%s.log',
            $this->kernel->getProjectDir(),
            date('Y-m-d_H-i-s')
        );
    }

    public function import(string $filePath, bool $isTest = false): ImportResult
    {
        $result = new ImportResult();

        foreach ($this->csvReader->readFile($filePath) as $dto) {
            $result->incrementProcessed();

            $errors = $this->validator->validateDto($dto);
            if ($errors) {
                $this->logError($dto->code, implode('; ', $errors));
                $result->incrementSkipped();
                continue;
            }

            foreach ($this->rules as $rule) {
                if (!$rule->isAllowed($dto)) {
                    $result->incrementSkipped();
                    $this->logError($dto->code, 'Skipped by rule: ' . get_class($rule));
                    continue 2; // skip this record and move on
                }
            }

            if ($isTest) {
                $result->incrementSuccess();
                continue;
            }

            try {
                $isUpdated = $this->productService->save($dto);
                $result->incrementSuccess();
                if ($isUpdated) {
                    $result->incrementUpdated();
                }
            } catch (\Exception $e) {
                $this->logError($dto->code, $e->getMessage());
                $result->incrementSkipped();
            }
        }

        return $result;
    }

    private function logError(string $code, string $message): void
    {
        file_put_contents(
            $this->errorLogPath,
            sprintf("[%s] Code: %s - %s\n", date('Y-m-d H:i:s'), $code, $message),
            FILE_APPEND
        );
    }

    public function getErrorLogPath(): string
    {
        return $this->errorLogPath;
    }

    public function getDuplicates(): array
    {
        return $this->duplicates;
    }
}
