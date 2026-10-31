<?php

declare(strict_types=1);

namespace App\Forge\Plan;

use App\Forge\Blueprint\Blueprint;

final class GenerationPlan
{
    /**
     * @param list<PlanOperation> $operations
     */
    public function __construct(
        public readonly Blueprint $blueprint,
        public readonly string $destination,
        public readonly array $operations,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'blueprint' => $this->blueprint->toArray(),
            'destination' => $this->destination,
            'operations' => array_map(
                static fn (PlanOperation $op): array => $op->toArray(),
                $this->operations
            ),
        ];
    }
}
