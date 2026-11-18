<?php
declare(strict_types=1);

namespace TrailSense\Core;

/** Renders a template from views/ inside views/layout.php. */
final class View
{
    public static function render(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        $content = self::partial($template, $data);
        $title = $data['title'] ?? 'TrailSense';
        $active = $data['active'] ?? '';
        $withMap = !empty($data['withMap']);
        $bodyClass = (string) ($data['bodyClass'] ?? '');
        $scripts = array_values(array_filter((array) ($data['scripts'] ?? []), fn ($s) => preg_match('/^[a-z0-9-]+\.js$/', (string) $s) === 1));
        $flash = Flash::take();
        $user = Auth::user();
        require TS_ROOT . '/views/layout.php';
    }

    public static function partial(string $template, array $data = []): string
    {
        $file = TS_ROOT . '/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \LogicException("View not found: $template");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    public static function redirect(string $route, array $params = []): void
    {
        header('Location: ' . url($route, $params), true, 303);
    }
}
