<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Filesystem;

use MohamedZaki\LaravelProcessBuilder\Exceptions\PhpSyntaxException;
use MohamedZaki\LaravelProcessBuilder\Filesystem\AtomicFileWriter;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class AtomicFileWriterTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/pb-writer-test-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);

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

    public function test_it_writes_a_file_and_creates_missing_directories(): void
    {
        $path = $this->directory.'/nested/dir/file.php';

        (new AtomicFileWriter())->write($path, "<?php\n\necho 'hi';\n");

        $this->assertFileExists($path);
        $this->assertStringContainsString("echo 'hi';", (string) file_get_contents($path));
    }

    public function test_it_rejects_invalid_php_syntax(): void
    {
        $path = $this->directory.'/broken.php';

        $this->expectException(PhpSyntaxException::class);

        (new AtomicFileWriter())->write($path, "<?php\n\nclass {{{ broken");
    }

    public function test_a_failed_syntax_check_leaves_no_temp_file_behind(): void
    {
        $path = $this->directory.'/broken.php';

        try {
            (new AtomicFileWriter())->write($path, "<?php\n\nclass {{{ broken");
        } catch (PhpSyntaxException) {
            // expected
        }

        $this->assertFileDoesNotExist($path);
        $this->assertEmpty(glob($this->directory.'/*.tmp') ?: []);
    }

    public function test_it_skips_syntax_validation_when_disabled(): void
    {
        $path = $this->directory.'/broken.php';

        (new AtomicFileWriter())->write($path, "<?php\n\nclass {{{ broken", validateSyntax: false);

        $this->assertFileExists($path);
    }
}
