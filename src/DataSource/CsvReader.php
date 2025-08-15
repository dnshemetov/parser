<?php

namespace App\DataSource;

use App\Mapper\ProductMapper;
use Generator;
use League\Csv\Reader;
use League\Csv\UnavailableStream;
use RuntimeException;
use Symfony\Component\HttpKernel\KernelInterface;

class CsvReader implements DataSourceInterface
{
    public function __construct(private readonly KernelInterface        $kernel)
    {

    }

    public function readFile(string $filePath): Generator
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
            $this->log("Can't access CSV file: $filePath. " . $e->getMessage());
            throw new RuntimeException("Can't access CSV file: $filePath");
        } catch (\League\Csv\Exception $e) {
            $this->log("Malformed CSV: $filePath. " . $e->getMessage());
            throw new RuntimeException("Malformed CSV: $filePath");
        } catch (\Exception $e) {
            $this->log("CSV parsing failed: " . $e->getMessage());
            throw new RuntimeException("CSV parsing failed: " . $e->getMessage());
        }
    }

    private function log(string $message): void
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