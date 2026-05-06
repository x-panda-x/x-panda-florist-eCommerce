<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

final class SettingsService
{
    private Application $app;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $cache = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    /**
     * @return array<string, mixed>
     */
    private function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $rows = $this->app->database()->fetchAll(
            'SELECT setting_key, setting_value FROM settings'
        );

        $settings = [];

        foreach ($rows as $row) {
            $key = (string) ($row['setting_key'] ?? '');

            if ($key === '') {
                continue;
            }

            $settings[$key] = $row['setting_value'] ?? null;
        }

        $this->cache = $settings;

        return $this->cache;
    }
}
