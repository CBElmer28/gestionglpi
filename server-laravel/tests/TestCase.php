<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Con SQLite en memoria las tablas no existen todavía.
        // Corremos las migraciones automáticamente para que DatabaseTransactions
        // pueda envolver los tests correctamente sin afectar datos reales.
        if (config('database.default') === 'sqlite') {
            $this->artisan('migrate');
        }
    }
}

