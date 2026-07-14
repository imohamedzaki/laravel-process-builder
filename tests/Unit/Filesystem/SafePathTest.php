<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Filesystem;

use MohamedZaki\LaravelProcessBuilder\Exceptions\UnsafeOutputPathException;
use MohamedZaki\LaravelProcessBuilder\Filesystem\SafePath;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class SafePathTest extends TestCase
{
    public function test_it_accepts_kebab_case_slugs(): void
    {
        $this->assertTrue(SafePath::isSafeSlug('create-order'));
        $this->assertTrue(SafePath::isSafeSlug('a'));
        $this->assertTrue(SafePath::isSafeSlug('order-123'));
    }

    public function test_it_rejects_unsafe_slugs(): void
    {
        $this->assertFalse(SafePath::isSafeSlug(''));
        $this->assertFalse(SafePath::isSafeSlug('../../etc/passwd'));
        $this->assertFalse(SafePath::isSafeSlug('Has Spaces'));
        $this->assertFalse(SafePath::isSafeSlug('Upper_Case'));
        $this->assertFalse(SafePath::isSafeSlug('trailing-'));
    }

    public function test_resolve_within_throws_for_traversal_attempts(): void
    {
        $this->expectException(UnsafeOutputPathException::class);

        SafePath::resolveWithin('/base/dir', '../../etc/passwd.json');
    }

    public function test_resolve_within_produces_a_path_inside_the_base_directory(): void
    {
        $path = SafePath::resolveWithin('/base/dir', 'create-order.json');

        $this->assertStringStartsWith('/base/dir', $path);
        $this->assertStringEndsWith('create-order.json', $path);
    }
}
