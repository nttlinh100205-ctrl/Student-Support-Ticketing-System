<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test trang gốc chuyển hướng đến trang tra cứu hỗ trợ.
     */
    public function test_the_application_redirects_from_root_to_catalog(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/catalog');
    }

    /**
     * Các trang giao diện của Module 2 hiển thị được.
     */
    public function test_module_pages_render(): void
    {
        foreach ([
            '/catalog',
            '/admin/departments',
            '/admin/support-types',
            '/admin/support-type-fields',
            '/admin/department-staff',
            '/admin/faqs',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}