<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DeploymentDownloadContractTest extends TestCase
{
    private function controllerSource(): string
    {
        return file_get_contents(dirname(__DIR__, 2) . '/app/Http/Controllers/Admin/DeploymentController.php');
    }

    public function test_project_zip_contains_database_public_root_and_local_install_guide(): void
    {
        $source = $this->controllerSource();

        self::assertStringContainsString('$zip->addFile($sqlFilePath, \'database/\' . $sqlFileName);', $source);
        self::assertStringContainsString('$zipRelativePath = \'public-root/\'', $source);
        self::assertStringContainsString("README-DEPLOYMENT.md", $source);
        self::assertStringContainsString('Instalasi Lokal', $source);
        self::assertStringContainsString('php artisan key:generate', $source);
        self::assertStringContainsString('php artisan storage:link', $source);
        self::assertStringContainsString('npm run build', $source);
    }

    public function test_public_root_resolution_supports_production_and_local_paths(): void
    {
        $source = $this->controllerSource();

        self::assertStringContainsString("env('SPMB_PUBLIC_ROOT')", $source);
        self::assertStringContainsString("dirname(base_path()) . '/public_html/web/www.seleksi'", $source);
        self::assertStringContainsString("base_path('public')", $source);
    }

    public function test_database_download_and_project_zip_use_the_same_sql_generator(): void
    {
        $source = $this->controllerSource();

        self::assertStringContainsString('private function generateSqlBackupFilePath(string $sqlFileName): string', $source);
        self::assertStringContainsString('$this->generateSqlBackupFilePath($sqlFileName)', $source);
        self::assertStringNotContainsString('$this->generateSqlBackup($sqlFileName)', $source);
    }
}
