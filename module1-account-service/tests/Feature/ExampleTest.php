<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_redirects_from_root_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
