<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->assertTestDatabaseIsIsolated();
        parent::setUp();
    }

    private function assertTestDatabaseIsIsolated(): void
    {
        $database = (string) ($_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: '');
        $connection = (string) ($_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: '');
        $username = (string) ($_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: '');
        $forbiddenDatabases = [
            'spmb_alfurqon',
            'spmb_staging',
            'sman5479_spmb',
            'pkgenerus_testing',
        ];

        if ($database !== 'spmb_testing' || in_array($database, $forbiddenDatabases, true)) {
            throw new \RuntimeException('Refusing tests: DB_DATABASE must be exactly spmb_testing.');
        }

        if ($connection !== 'mysql') {
            throw new \RuntimeException('Refusing tests: DB_CONNECTION must be mysql.');
        }

        if ($username !== 'spmb_testing_user' || $username === 'root') {
            throw new \RuntimeException('Refusing tests: DB_USERNAME must be the dedicated spmb_testing_user.');
        }

        $password = (string) ($_ENV['SPMB_TEST_DB_PASSWORD'] ?? getenv('SPMB_TEST_DB_PASSWORD') ?: '');
        if ($password === '') {
            throw new \RuntimeException('Refusing tests: SPMB_TEST_DB_PASSWORD must be supplied through the test secret environment.');
        }
    }
}
