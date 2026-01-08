<?php

namespace App\DataSource;

use Generator;

interface DataSourceInterface
{
    public function readFile(string $filePath): Generator;
}