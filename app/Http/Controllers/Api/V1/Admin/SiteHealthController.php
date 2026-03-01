<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Site Health diagnostics — reports on system environment,
 * database connectivity, disk space, and PHP extensions.
 */
class SiteHealthController extends ApiController
{
    #[OA\Get(
        path: "/api/v1/admin/tools/site-health",
        operationId: "siteHealth",
        summary: "Get site health diagnostics",
        tags: ["Admin Tools"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(response: 200, description: "System tests and info"),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        $tests = $this->runTests();
        $info = $this->gatherInfo();

        return $this->success([
            'tests' => $tests,
            'info' => $info,
        ]);
    }

    /**
     * Run diagnostic tests and return pass/fail/recommendation results.
     *
     * @return array<int, array{name: string, status: string, description: string}>
     */
    private function runTests(): array
    {
        $tests = [];

        // PHP version check
        $phpVersion = PHP_VERSION;
        $isPhpOk = version_compare($phpVersion, '8.2.0', '>=');
        $tests[] = [
            'name' => 'PHP Version',
            'status' => $isPhpOk ? 'good' : 'critical',
            'description' => $isPhpOk
                ? "PHP {$phpVersion} meets the requirement (≥ 8.2)."
                : "PHP {$phpVersion} is below the required 8.2.",
        ];

        // Database connection
        try {
            DB::connection()->getPdo();
            $tests[] = [
                'name' => 'Database Connection',
                'status' => 'good',
                'description' => 'Database connection is active.',
            ];
        } catch (\Throwable $e) {
            $tests[] = [
                'name' => 'Database Connection',
                'status' => 'critical',
                'description' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }

        // Required extensions
        $requiredExtensions = ['pdo', 'mbstring', 'openssl', 'tokenizer', 'json', 'fileinfo'];
        $missingExtensions = array_filter($requiredExtensions, fn($ext) => !extension_loaded($ext));

        $tests[] = [
            'name' => 'Required PHP Extensions',
            'status' => empty($missingExtensions) ? 'good' : 'critical',
            'description' => empty($missingExtensions)
                ? 'All required extensions are loaded.'
                : 'Missing extensions: ' . implode(', ', $missingExtensions),
        ];

        // Disk space
        $freeBytes = @disk_free_space(storage_path());
        if ($freeBytes !== false) {
            $freeGb = round($freeBytes / 1073741824, 2);
            $tests[] = [
                'name' => 'Available Disk Space',
                'status' => $freeGb > 1 ? 'good' : ($freeGb > 0.25 ? 'recommended' : 'critical'),
                'description' => "{$freeGb} GB free on storage disk.",
            ];
        }

        // Storage writable
        $isStorageWritable = is_writable(storage_path());
        $tests[] = [
            'name' => 'Storage Directory Writable',
            'status' => $isStorageWritable ? 'good' : 'critical',
            'description' => $isStorageWritable
                ? 'Storage directory is writable.'
                : 'Storage directory is NOT writable.',
        ];

        return $tests;
    }

    /**
     * Gather environment information for administrative review.
     *
     * @return array<string, mixed>
     */
    private function gatherInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'db_driver' => config('database.default'),
            'db_version' => $this->getDatabaseVersion(),
            'timezone' => config('app.timezone'),
            'memory_limit' => ini_get('memory_limit'),
            'max_upload_size' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'extensions' => get_loaded_extensions(),
        ];
    }

    private function getDatabaseVersion(): string
    {
        try {
            return DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } catch (\Throwable) {
            return 'unknown';
        }
    }
}
