<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Audit;

use MohamedZaki\LaravelProcessBuilder\Audit\AuditLogger;
use MohamedZaki\LaravelProcessBuilder\Enums\AuditAction;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class AuditLoggerTest extends TestCase
{
    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = sys_get_temp_dir().'/pb-audit-test-'.bin2hex(random_bytes(6)).'/audit.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logPath)) {
            unlink($this->logPath);
        }

        $directory = dirname($this->logPath);

        if (is_dir($directory)) {
            rmdir($directory);
        }

        parent::tearDown();
    }

    public function test_it_creates_the_log_directory_and_appends_a_json_line(): void
    {
        $logger = new AuditLogger($this->logPath);

        $logger->record(AuditAction::ProcessCreated, 'success', 'process-id', 1);

        $this->assertFileExists($this->logPath);

        $lines = array_filter(explode(PHP_EOL, (string) file_get_contents($this->logPath)));
        $this->assertCount(1, $lines);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) reset($lines), true);

        $this->assertSame('process_created', $decoded['action']);
        $this->assertSame('success', $decoded['result']);
        $this->assertSame('process-id', $decoded['processId']);
        $this->assertSame(1, $decoded['processVersion']);
        $this->assertNotEmpty($decoded['correlationId']);
        $this->assertNotEmpty($decoded['timestamp']);
    }

    public function test_it_appends_multiple_entries_without_overwriting(): void
    {
        $logger = new AuditLogger($this->logPath);

        $logger->record(AuditAction::ProcessCreated, 'success', 'process-id', 1);
        $logger->record(AuditAction::ProcessUpdated, 'success', 'process-id', 2);

        $lines = array_filter(explode(PHP_EOL, (string) file_get_contents($this->logPath)));

        $this->assertCount(2, $lines);
    }

    public function test_it_never_records_a_secret_or_sensitive_payload_field(): void
    {
        $logger = new AuditLogger($this->logPath);

        $entry = $logger->record(AuditAction::GenerationCompleted, 'success', 'process-id', 1, ['app/OrderController.php']);

        $this->assertSame(['app/OrderController.php'], $entry->changedFiles);
        $this->assertArrayNotHasKey('secret', $entry->toArray());
        $this->assertArrayNotHasKey('payload', $entry->toArray());
    }
}
