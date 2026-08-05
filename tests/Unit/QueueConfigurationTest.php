<?php

namespace Tests\Unit;

use Tests\TestCase;

class QueueConfigurationTest extends TestCase
{
    public function test_redis_retry_after_exceeds_every_horizon_worker_timeout(): void
    {
        $timeouts = array_map(
            fn (array $supervisor): int => $supervisor['timeout'],
            config('horizon.defaults'),
        );

        $this->assertGreaterThan(max($timeouts), config('queue.connections.redis.retry_after'));
    }
}
