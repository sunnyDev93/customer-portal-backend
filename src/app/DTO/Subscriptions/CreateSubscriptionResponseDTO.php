<?php

declare(strict_types=1);

namespace App\DTO\Subscriptions;

final class CreateSubscriptionResponseDTO
{
    public function __construct(
        public readonly int $subscriptionId
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'subscriptionId' => $this->subscriptionId,
        ];
    }
}
