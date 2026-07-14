<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_halaman_root_mengalihkan_ke_daftar_transaksi(): void
    {
        $this->get('/')->assertRedirect(route('transaksi.index'));
    }
}
