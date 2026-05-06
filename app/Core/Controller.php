<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $view, array $data = [], ?string $layout = null): string
    {
        return View::render($this->app, $view, $data, $layout);
    }
}
