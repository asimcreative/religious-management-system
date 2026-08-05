<?php

namespace Tests\Unit;

use Tests\TestCase;

class DeploymentScriptTest extends TestCase
{
    public function test_authenticated_deployments_are_serialized(): void
    {
        $script = file_get_contents(public_path('deploy.php'));

        $this->assertIsString($script);
        $this->assertStringContainsString("function_exists('fastcgi_finish_request')", $script);
        $this->assertMatchesRegularExpression(
            '/flock\(\$deployLock,\s*LOCK_EX\s*\|\s*LOCK_NB\)/',
            $script,
        );
        $this->assertGreaterThan(
            strpos($script, 'hash_equals'),
            strpos($script, 'flock($deployLock, LOCK_EX | LOCK_NB)'),
        );
        $this->assertGreaterThan(
            strpos($script, 'flock($deployLock, LOCK_EX | LOCK_NB)'),
            strpos($script, 'deployAcknowledge(['),
        );
        $this->assertGreaterThan(
            strpos($script, 'deployAcknowledge(['),
            strpos($script, '$previousCommit'),
        );
    }
}
