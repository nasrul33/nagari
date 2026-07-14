<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_halaman_root_mengalihkan_ke_dashboard(): void
    {
        $this->get('/')->assertRedirect(route('dashboard'));
    }
}
