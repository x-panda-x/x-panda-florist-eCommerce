<?php

declare(strict_types=1);

final class LiveSiteHarness
{
    private string $baseUrl;
    private string $cookieFile;

    public function __construct(string $baseUrl, ?string $cookieFile = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cookieFile = $cookieFile ?? tempnam(sys_get_temp_dir(), 'sheyda-cookies-');
    }

    /**
     * @param array<string, scalar|null> $data
     * @param array<string, mixed> $options
     * @return array{status:int, headers:array<string, array<int, string>>, body:string, location:string|null, url:string}
     */
    public function get(string $path, array $options = []): array
    {
        return $this->request('GET', $path, [], $options);
    }

    /**
     * @param array<string, scalar|null> $data
     * @param array<string, mixed> $options
     * @return array{status:int, headers:array<string, array<int, string>>, body:string, location:string|null, url:string}
     */
    public function post(string $path, array $data, array $options = []): array
    {
        return $this->request('POST', $path, $data, $options);
    }

    public function absoluteUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * @param array<string, scalar|null> $data
     * @param array<string, mixed> $options
     * @return array{status:int, headers:array<string, array<int, string>>, body:string, location:string|null, url:string}
     */
    private function request(string $method, string $path, array $data, array $options): array
    {
        $url = $this->absoluteUrl($path);
        $curl = curl_init($url);

        if ($curl === false) {
            throw new RuntimeException('Unable to initialize cURL.');
        }

        $followLocation = (bool) ($options['follow_location'] ?? false);
        $extraHeaders = $options['headers'] ?? [];
        $payload = '';

        if ($method === 'POST') {
            $payload = http_build_query($data);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
            $extraHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => $followLocation,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $extraHeaders,
            CURLOPT_USERAGENT => 'SheydaFloristLiveHarness/1.0',
        ]);

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException('HTTP request failed: ' . $error);
        }

        $headerSize = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $headerBlock = substr($response, 0, $headerSize);
        $body = (string) substr($response, $headerSize);
        curl_close($curl);

        return [
            'status' => $status,
            'headers' => self::parseHeaders($headerBlock),
            'body' => $body,
            'location' => self::firstHeader(self::parseHeaders($headerBlock), 'location'),
            'url' => $url,
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function parseHeaders(string $headerBlock): array
    {
        $headers = [];
        $blocks = preg_split("/\r\n\r\n|\n\n/", trim($headerBlock)) ?: [];
        $lastBlock = $blocks !== [] ? (string) end($blocks) : $headerBlock;
        $lines = preg_split("/\r\n|\n/", $lastBlock) ?: [];

        foreach ($lines as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $name = strtolower(trim($name));
            $headers[$name][] = trim($value);
        }

        return $headers;
    }

    /**
     * @param array<string, array<int, string>> $headers
     */
    private static function firstHeader(array $headers, string $name): ?string
    {
        $normalized = strtolower($name);

        return $headers[$normalized][0] ?? null;
    }

    public static function extractCsrfToken(string $html): ?string
    {
        return self::extractInputValue($html, 'csrf_token');
    }

    public static function extractInputValue(string $html, string $name): ?string
    {
        $pattern = '/name="' . preg_quote($name, '/') . '"[^>]*value="([^"]*)"/i';

        return preg_match($pattern, $html, $matches) === 1 ? html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8') : null;
    }

    public static function extractCheckedVariantId(string $html): ?string
    {
        if (preg_match('/name="variant_id"[^>]*value="(\d+)"[^>]*checked/i', $html, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/name="variant_id"[^>]*value="(\d+)"/i', $html, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    public static function extractFirstMatch(string $html, string $pattern): ?string
    {
        return preg_match($pattern, $html, $matches) === 1 ? $matches[1] : null;
    }

    public static function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }
}
