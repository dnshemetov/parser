<?php

namespace App\Command;

use App\Service\ProductImporter;
use App\Dto\ImportResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Symfony Console Command responsible for importing products from a CSV file.
 *
 * Usage:
 *   php bin/console app:import-products <filename> [directory] [--test]
 *
 * Arguments:
 *   filename  - The name of the CSV file to import
 *   directory - (optional) Directory where the CSV file is located (default: var/import)
 *
 * Options:
 *   --test    - Run in test mode (data will be validated but not saved to the database)
 */
#[AsCommand(
    name: 'app:import-products',
    description: 'Import products from a CSV file'
)]
class ImportProductsCommand extends Command
{
    public function __construct(
        private ProductImporter $importer,
        private KernelInterface $kernel
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED, 'CSV filename')
            ->addArgument('directory', InputArgument::OPTIONAL, 'Import directory', 'var/import')
            ->addOption('test', null, InputOption::VALUE_NONE, 'Run in test mode (no DB insert)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Read arguments
        $filename = $input->getArgument('filename');
        $directory = $input->getArgument('directory');
        $isTest = $input->getOption('test');

        // Build absolute directory path
        $directoryPath = rtrim($this->kernel->getProjectDir() . '/' . $directory, '/');

        // Create the directory if it does not exist
        if (!is_dir($directoryPath)) {
            if (!mkdir($directoryPath, 0777, true) && !is_dir($directoryPath)) {
                $output->writeln("<error>Failed to create directory: {$directoryPath}</error>");
                return Command::FAILURE;
            }
            $output->writeln("<comment>Directory '{$directoryPath}' was created automatically.</comment>");
        }

        // Build the full path to the CSV file
        $filePath = $directoryPath . '/' . $filename;

        // Display start message
        $output->writeln('<info>Starting product import...</info>');
        if ($isTest) {
            $output->writeln('<comment>Running in TEST mode. No data will be inserted.</comment>');
        }

        // Run the import
        /** @var ImportResult $result */
        $result = $this->importer->import($filePath, $isTest);

        // Show import summary
        $output->writeln("\n<info>--- Import Summary ---</info>");
        $output->writeln("Processed: <info>{$result->getProcessed()}</info>");
        $output->writeln("Success:   <info>{$result->getSuccess()}</info>");
        $output->writeln("Updated:   <info>{$result->getUpdated()}</info>");
        $output->writeln("Skipped:   <comment>{$result->getSkipped()}</comment>");

        // Display errors and duplicates if any
        if (count($result->getErrors()) > 0) {
            $output->writeln("\n<error>Some rows were skipped or failed to import.</error>");
            $output->writeln("Errors found: <comment>" . count($result->getErrors()) . "</comment>");
            $output->writeln("See detailed log here: <info>{$this->importer->getErrorLogPath()}</info>\n");

            $duplicates = $this->importer->getDuplicates();
            if (!empty($duplicates)) {
                $output->writeln("<comment>Duplicate Product Codes detected:</comment>");
                foreach ($duplicates as $code => $count) {
                    $output->writeln(" - {$code} appears {$count} times");
                }
                $output->writeln('');
            }
        }

        return Command::SUCCESS;
    }
}
