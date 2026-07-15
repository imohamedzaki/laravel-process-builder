<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Security;

use MohamedZaki\LaravelProcessBuilder\Exceptions\InvalidPreviewTokenException;
use MohamedZaki\LaravelProcessBuilder\Security\PreviewTokenSigner;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class PreviewTokenSignerTest extends TestCase
{
    public function test_it_signs_and_verifies_a_token(): void
    {
        $signer = new PreviewTokenSigner('test-secret-key', 600);

        $token = $signer->sign('PROC123', 1, 'checksum-abc');

        $verified = $signer->verify($token);

        $this->assertSame('PROC123', $verified->processId);
        $this->assertSame(1, $verified->processVersion);
        $this->assertSame('checksum-abc', $verified->definitionChecksum);
        $this->assertFalse($verified->isExpired());
    }

    public function test_it_rejects_a_tampered_token(): void
    {
        $signer = new PreviewTokenSigner('test-secret-key', 600);

        $token = $signer->sign('PROC123', 1, 'checksum-abc');
        $tampered = substr($token, 0, -1).'x';

        $this->expectException(InvalidPreviewTokenException::class);

        $signer->verify($tampered);
    }

    public function test_it_rejects_a_token_signed_with_a_different_key(): void
    {
        $signerA = new PreviewTokenSigner('key-a', 600);
        $signerB = new PreviewTokenSigner('key-b', 600);

        $token = $signerA->sign('PROC123', 1, 'checksum-abc');

        $this->expectException(InvalidPreviewTokenException::class);

        $signerB->verify($token);
    }

    public function test_it_rejects_a_malformed_token(): void
    {
        $signer = new PreviewTokenSigner('test-secret-key', 600);

        $this->expectException(InvalidPreviewTokenException::class);

        $signer->verify('not-a-valid-token');
    }

    public function test_it_rejects_an_expired_token(): void
    {
        $signer = new PreviewTokenSigner('test-secret-key', -1);

        $token = $signer->sign('PROC123', 1, 'checksum-abc');

        $this->expectException(InvalidPreviewTokenException::class);

        $signer->verify($token);
    }
}
