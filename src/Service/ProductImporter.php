<?php

namespace App\Service;

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
        private readonly EntityManagerInterface $em,
        private readonly ProductValidator       $validator,
        private readonly iterable               $rules,
        private readonly KernelInterface        $kernel
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

        foreach ($this->readCsv($filePath) as $dto) {
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
                $isUpdated = $this->save($dto);
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

    protected function readCsv(string $filePath): Generator
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("CSV file not found: $filePath");
        }

        try {
            $csv = Reader::createFromPath($filePath);
            $csv->setHeaderOffset(0);

            foreach ($csv->getRecords() as $record) {
                yield ProductMapper::fromRecord($record);
            }
        } catch (UnavailableStream $e) {
            $this->logCsvError("Can't access CSV file: $filePath. " . $e->getMessage());
            throw new RuntimeException("Can't access CSV file: $filePath");
        } catch (\League\Csv\Exception $e) {
            $this->logCsvError("Malformed CSV: $filePath. " . $e->getMessage());
            throw new RuntimeException("Malformed CSV: $filePath");
        } catch (\Exception $e) {
            $this->logCsvError("CSV parsing failed: " . $e->getMessage());
            throw new RuntimeException("CSV parsing failed: " . $e->getMessage());
        }
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

    protected function save(ProductDto $dto): bool
    {
        $conn = $this->em->getConnection();

        $sql = '
            INSERT INTO tblProductData 
                (strProductCode, strProductName, strProductDesc, intStock, decPrice, dtmDiscontinued, dtmAdded)
            VALUES 
                (:code, :name, :desc, :stock, :price, :discontinued, NOW())
            ON DUPLICATE KEY UPDATE
                strProductName = VALUES(strProductName),
                strProductDesc = VALUES(strProductDesc),
                intStock = VALUES(intStock),
                decPrice = VALUES(decPrice),
                dtmDiscontinued = VALUES(dtmDiscontinued)
        ';

        $params = [
            'code' => $dto->code,
            'name' => $dto->name,
            'desc' => $dto->description,
            'stock' => $dto->stock ?? 0,
            'price' => $dto->price ?? 0,
            'discontinued' => $dto->discontinued ? (new DateTime())->format('Y-m-d H:i:s') : null,
        ];

        try {
            $conn->executeStatement($sql, $params);

            // 1 = insert, 2 = update (ON DUPLICATE KEY UPDATE)
            $rowCount = $conn->executeQuery('SELECT ROW_COUNT()')->fetchOne();

            return $rowCount === 2;
        } catch (Exception $e) {
            $this->logError($dto->code, $e->getMessage());
        }

        return false;
    }

    private function logCsvError(string $message): void
    {
        $logDir = $this->kernel->getProjectDir() . '/var/log';
        $logFile = $logDir . '/csv_errors_' . date('Y-m-d_H-i-s') . '.log';

        file_put_contents(
            $logFile,
            sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message),
            FILE_APPEND
        );
    }
}
