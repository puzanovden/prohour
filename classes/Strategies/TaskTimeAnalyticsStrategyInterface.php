<?php

namespace App\Strategies;

interface TaskTimeAnalyticsStrategyInterface
{
    public function calculate(array $tasks): array;

    public function getName(): string;
}