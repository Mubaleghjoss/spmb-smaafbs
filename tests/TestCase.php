<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function refreshApplication()
    {
        parent::refreshApplication();
        $this->assertTestDatabaseIsIsolated();
    }

    private function assertTestDatabaseIsIsolated(): void
    {
        $config = $this->app['config'];
        $environment = (string) $config->get('app.env', '');
        $defaultConnection = (string) $config->get('database.default', '');
        $connection = $config->get("database.connections.{$defaultConnection}");
        $database = is_array($connection) ? (string) ($connection['database'] ?? '') : '';
        $username = is_array($connection) ? (string) ($connection['username'] ?? '') : '';
        $host = is_array($connection) ? (string) ($connection['host'] ?? '') : '';

        if ($environment !== 'testing') {
            throw new \RuntimeException('Refusing tests: APP_ENV must be exactly testing.');
        }

        if ($defaultConnection !== 'mysql' || ! is_array($connection)) {
            throw new \RuntimeException('Refusing tests: the resolved testing database connection must be mysql.');
        }

        if (preg_match('/^spmb_testing(?:_[A-Za-z0-9_-]+)?$/', $database) !== 1) {
            throw new \RuntimeException('Refusing tests: the resolved testing database is not isolated.');
        }

        if ($username !== 'spmb_testing_user') {
            throw new \RuntimeException('Refusing tests: the resolved testing database user must be spmb_testing_user.');
        }

        if (! in_array($host, ['localhost', '127.0.0.1'], true)) {
            throw new \RuntimeException('Refusing tests: the resolved testing database host must be local.');
        }

        $password = (string) ($_ENV['SPMB_TEST_DB_PASSWORD'] ?? getenv('SPMB_TEST_DB_PASSWORD') ?: '');
        if ($password === '') {
            throw new \RuntimeException('Refusing tests: SPMB_TEST_DB_PASSWORD must be supplied through the test secret environment.');
        }
    }
}
