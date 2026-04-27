<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(\Qameta\Allure\Allure::class)) {
            
            $db = config('database.default');
            $queue = config('queue.default');

            $testMode = 'Predeterminado';
            if ($db === 'sqlite') {
                $testMode = 'Síncrona (SQLite)';
            } elseif ($db === 'mysql') {
                $testMode = ($queue === 'sync') ? 'Síncrona (MySQL)' : 'Asíncrona (MySQL)';
            }

            \Qameta\Allure\Allure::label('test_mode', $testMode);
            \Qameta\Allure\Allure::label('db_connection', $db);
            \Qameta\Allure\Allure::label('queue_connection', $queue);
        }
    }
}