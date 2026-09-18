<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test trang gốc chuyển hướng đến trang danh sách yêu cầu.
     */
    public function test_the_application_redirects_from_root_to_requests(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/requests');
    }
}