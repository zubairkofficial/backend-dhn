<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class ExternalProcessingClient
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(config('processing'));
    }

    public function makeHttpClient(): Client
    {
        return new Client([
            'timeout' => $this->config['timeout'],
            'connect_timeout' => $this->config['connect_timeout'],
            'read_timeout' => $this->config['read_timeout'],
            'http_errors' => false,
        ]);
    }

    public function endpointUrl(string $endpointKey): string
    {
        $base = rtrim((string) $this->config['base_url'], '/');
        $path = $this->config['endpoints'][$endpointKey] ?? null;
        if ($path === null || $path === '') {
            throw new InvalidArgumentException("Unknown processing endpoint key: {$endpointKey}");
        }
        $path = '/'.ltrim((string) $path, '/');

        return $base.$path;
    }

    /**
     * @param  array<string, string>  $extraMultipartFields  Fields inserted before the document part (e.g. doctype).
     */
    public function postMultipart(
        string $endpointKey,
        string $localFilePath,
        string $originalFileName,
        array $extraMultipartFields = [],
        array $logContext = [],
    ): ResponseInterface {
        $password = (string) ($this->config['password'] ?? '');
        if ($password === '') {
            throw new InvalidArgumentException(
                'PROCESSING_SERVICE_PASSWORD is not set. Configure .env before calling the external processing service.'
            );
        }

        $username = (string) $this->config['username'];

        $multipart = [
            ['name' => 'username', 'contents' => $username],
            ['name' => 'password', 'contents' => $password],
        ];

        foreach ($extraMultipartFields as $name => $value) {
            $multipart[] = ['name' => $name, 'contents' => $value];
        }

        $multipart[] = [
            'name' => 'document',
            'contents' => fopen($localFilePath, 'r'),
            'filename' => $originalFileName,
        ];

        $url = $this->endpointUrl($endpointKey);
        $ctx = array_merge([
            'endpoint_key' => $endpointKey,
            'url' => $url,
            'file' => $originalFileName,
        ], $logContext);

        Log::info('processing.request', $ctx);

        try {
            $response = $this->makeHttpClient()->post($url, [
                'auth' => [$username, $password],
                'multipart' => $multipart,
            ]);
        } catch (GuzzleException $e) {
            Log::error('processing.request_failed', array_merge($ctx, [
                'error' => $e->getMessage(),
            ]));
            throw $e;
        }

        Log::info('processing.response', array_merge($ctx, [
            'status' => $response->getStatusCode(),
        ]));

        return $response;
    }
}
