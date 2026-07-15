<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Security;

use MohamedZaki\LaravelProcessBuilder\Exceptions\InvalidPreviewTokenException;

final class PreviewTokenSigner
{
    public function __construct(
        private readonly string $appKey,
        private readonly int $ttlSeconds,
    ) {
    }

    public function sign(string $processId, int $processVersion, string $definitionChecksum): string
    {
        $expiresAt = time() + $this->ttlSeconds;

        $payload = [
            'processId' => $processId,
            'processVersion' => $processVersion,
            'definitionChecksum' => $definitionChecksum,
            'expiresAt' => $expiresAt,
        ];

        $encodedPayload = base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->signHmac($encodedPayload);

        return $encodedPayload.'.'.$signature;
    }

    public function verify(string $token): PreviewToken
    {
        $parts = explode('.', $token, 2);

        if (count($parts) !== 2) {
            throw InvalidPreviewTokenException::invalid();
        }

        [$encodedPayload, $signature] = $parts;

        if (! hash_equals($this->signHmac($encodedPayload), $signature)) {
            throw InvalidPreviewTokenException::invalid();
        }

        $decoded = base64_decode($encodedPayload, strict: true);

        if ($decoded === false) {
            throw InvalidPreviewTokenException::invalid();
        }

        /** @var mixed $payload */
        $payload = json_decode($decoded, true);

        if (! is_array($payload)
            || ! isset($payload['processId'], $payload['processVersion'], $payload['definitionChecksum'], $payload['expiresAt'])
            || ! is_string($payload['processId'])
            || ! is_int($payload['processVersion'])
            || ! is_string($payload['definitionChecksum'])
            || ! is_int($payload['expiresAt'])
        ) {
            throw InvalidPreviewTokenException::invalid();
        }

        $previewToken = new PreviewToken(
            processId: $payload['processId'],
            processVersion: $payload['processVersion'],
            definitionChecksum: $payload['definitionChecksum'],
            expiresAt: $payload['expiresAt'],
        );

        if ($previewToken->isExpired()) {
            throw InvalidPreviewTokenException::expired();
        }

        return $previewToken;
    }

    private function signHmac(string $data): string
    {
        return hash_hmac('sha256', $data, $this->appKey);
    }
}
