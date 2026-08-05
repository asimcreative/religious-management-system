<?php

namespace Tests\Unit;

use Tests\TestCase;

class NginxSecurityConfigurationTest extends TestCase
{
    public function test_every_fastcgi_entry_point_clears_the_proxy_header(): void
    {
        $configuration = file_get_contents(base_path('nginx.conf'));

        $this->assertIsString($configuration);
        $this->assertSame(2, substr_count($configuration, 'fastcgi_param HTTP_PROXY'));
    }
}
