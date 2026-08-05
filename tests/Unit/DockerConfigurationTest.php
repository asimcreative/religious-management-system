<?php

namespace Tests\Unit;

use Tests\TestCase;

class DockerConfigurationTest extends TestCase
{
    public function test_redis_defaults_to_a_non_evicting_policy(): void
    {
        $compose = file_get_contents(base_path('docker-compose.yml'));

        $this->assertIsString($compose);
        $this->assertStringContainsString('"${REDIS_MAXMEMORY_POLICY:-noeviction}"', $compose);
        $this->assertStringNotContainsString('"allkeys-lru"', $compose);
    }

    public function test_production_sessions_are_encrypted(): void
    {
        $dockerEnvironment = file_get_contents(base_path('.env.docker'));
        $standardEnvironment = file_get_contents(base_path('.env.example'));

        $this->assertIsString($dockerEnvironment);
        $this->assertIsString($standardEnvironment);
        $this->assertStringContainsString('SESSION_ENCRYPT=true', $dockerEnvironment);
        $this->assertStringContainsString('SESSION_ENCRYPT=true', $standardEnvironment);
    }

    public function test_docker_images_exclude_the_checkout_based_webhook_deployer(): void
    {
        $dockerignore = file_get_contents(base_path('.dockerignore'));

        $this->assertIsString($dockerignore);
        $this->assertStringContainsString('public/deploy.php', $dockerignore);
    }
}
