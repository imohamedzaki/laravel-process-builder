<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Backup;

use MohamedZaki\LaravelProcessBuilder\Backup\BackupService;
use MohamedZaki\LaravelProcessBuilder\Exceptions\BackupNotFoundException;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class BackupServiceTest extends TestCase
{
    private string $backupsDirectory;

    private string $filesDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupsDirectory = sys_get_temp_dir().'/pb-backups-test-'.bin2hex(random_bytes(6));
        $this->filesDirectory = sys_get_temp_dir().'/pb-backup-files-'.bin2hex(random_bytes(6));

        mkdir($this->filesDirectory, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->backupsDirectory);
        $this->removeDirectory($this->filesDirectory);

        parent::tearDown();
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (glob($directory.'/*') ?: [] as $entry) {
            is_dir($entry) ? $this->removeDirectory($entry) : unlink($entry);
        }

        rmdir($directory);
    }

    public function test_it_creates_a_backup_of_existing_files(): void
    {
        $filePath = $this->filesDirectory.'/OrderController.php';
        file_put_contents($filePath, '<?php // original');

        $service = new BackupService($this->backupsDirectory);

        $metadata = $service->createBackup('create-order', ['app/Http/Controllers/OrderController.php' => $filePath]);

        $this->assertSame('create-order', $metadata->processSlug);
        $this->assertSame(['app/Http/Controllers/OrderController.php'], $metadata->backedUpRelativePaths);
    }

    public function test_it_skips_files_that_do_not_exist_yet(): void
    {
        $service = new BackupService($this->backupsDirectory);

        $metadata = $service->createBackup('create-order', ['does/not/exist.php' => $this->filesDirectory.'/missing.php']);

        $this->assertSame([], $metadata->backedUpRelativePaths);
    }

    public function test_it_lists_backups_most_recent_first(): void
    {
        $service = new BackupService($this->backupsDirectory);

        $first = $service->createBackup('create-order', []);
        usleep(1_100_000);
        $second = $service->createBackup('create-order', []);

        $backups = $service->listBackups('create-order');

        $this->assertCount(2, $backups);
        $this->assertSame($second->id, $backups[0]->id);
        $this->assertSame($first->id, $backups[1]->id);
    }

    public function test_finding_a_missing_backup_throws(): void
    {
        $service = new BackupService($this->backupsDirectory);

        $this->expectException(BackupNotFoundException::class);

        $service->findBackup('create-order', 'does-not-exist');
    }

    public function test_it_restores_a_backup_to_the_original_path(): void
    {
        $filePath = $this->filesDirectory.'/OrderController.php';
        file_put_contents($filePath, '<?php // original');

        $service = new BackupService($this->backupsDirectory);
        $backup = $service->createBackup('create-order', ['app/OrderController.php' => $filePath]);

        // Simulate the file being changed after the backup.
        file_put_contents($filePath, '<?php // modified');

        $service->restore('create-order', $backup->id, ['app/OrderController.php' => $filePath]);

        $this->assertSame('<?php // original', file_get_contents($filePath));
    }

    public function test_it_restores_a_backup_whose_relative_path_contains_a_windows_drive_letter(): void
    {
        // Regression: when process-builder.output.* points outside base_path(), the manifest's
        // "relativePath" can end up as a full absolute path (e.g. "C:/Users/.../File.php"). The
        // colon after the drive letter must not corrupt the backup's on-disk filename.
        $filePath = $this->filesDirectory.'/OrderController.php';
        file_put_contents($filePath, '<?php // original');

        $windowsStyleRelativePath = 'C:/Users/example/OrderController.php';

        $service = new BackupService($this->backupsDirectory);
        $backup = $service->createBackup('create-order', [$windowsStyleRelativePath => $filePath]);

        $this->assertSame([$windowsStyleRelativePath], $backup->backedUpRelativePaths);

        file_put_contents($filePath, '<?php // modified');

        $service->restore('create-order', $backup->id, [$windowsStyleRelativePath => $filePath]);

        $this->assertSame('<?php // original', file_get_contents($filePath));
    }

    public function test_it_enforces_retention_by_deleting_the_oldest_backups(): void
    {
        $service = new BackupService($this->backupsDirectory, retention: 2);

        $service->createBackup('create-order', []);
        usleep(1_100_000);
        $service->createBackup('create-order', []);
        usleep(1_100_000);
        $service->createBackup('create-order', []);

        $backups = $service->listBackups('create-order');

        $this->assertCount(2, $backups);
    }
}
