<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(Application $app, string $view, array $data = [], ?string $layout = null): string
    {
        $viewPath = $app->getBasePath('views/pages/' . $view . '.php');

        if (!is_file($viewPath)) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        $content = self::capture($viewPath, $data);

        if ($layout === null) {
            return $content;
        }

        $layoutPath = $app->getBasePath('views/layouts/' . $layout . '.php');

        if (!is_file($layoutPath)) {
            throw new \RuntimeException('Layout not found: ' . $layout);
        }

        return self::capture($layoutPath, array_merge($data, ['content' => $content]));
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function capture(string $path, array $data): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require $path;

        return (string) ob_get_clean();
    }
}
