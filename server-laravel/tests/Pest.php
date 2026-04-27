<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\DatabaseTransactions::class,
)
->beforeEach(function () {
    if (class_exists(\Qameta\Allure\Allure::class)) {
        // Priorizar $_SERVER o getenv() para CI
        $db = $_SERVER['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?? config('database.default');
        $queue = $_SERVER['QUEUE_CONNECTION'] ?? getenv('QUEUE_CONNECTION') ?? config('queue.default');

        \Qameta\Allure\Allure::label('db_connection', $db);
        \Qameta\Allure\Allure::label('queue_connection', $queue);

        // Lógica de Test Mode para el CSV
        $testMode = 'Predeterminado';
        if ($db === 'sqlite') {
            $testMode = 'Síncrona (SQLite)';
        } elseif ($db === 'mysql') {
            $testMode = ($queue === 'sync') ? 'Síncrona (MySQL)' : 'Asíncrona (MySQL)';
        }

        \Qameta\Allure\Allure::label('test_mode', $testMode);
    }
})
->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
