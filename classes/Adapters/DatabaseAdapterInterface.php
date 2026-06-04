<?php

namespace App\Adapters;

use PDO;

interface DatabaseAdapterInterface
{
    public function connect(): PDO;

    public function getName(): string;
}