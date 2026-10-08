<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Trang gốc cung cấp điều hướng đến các service.
     */
    public function test_the_application_renders_the_unified_portal(): void
    {
        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
        $response = $this->get('/');

        $response->assertOk()->assertSee('http://localhost:8003')->assertSee('Cổng hỗ trợ sinh viên');
    }
}
