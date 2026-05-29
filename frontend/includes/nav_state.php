<?php

if (!function_exists('jt_normalize_nav_path')) {
    function jt_normalize_nav_path(?string $requestUri, string $baseUrl): string
    {
        $path = strtok((string) $requestUri, '?') ?: '';
        $path = rtrim($path, '/');
        $basePath = rtrim($baseUrl, '/');

        if ($path === '') {
            $path = $basePath;
        }

        $aliases = [
            $basePath => $basePath . '/explore',
            $basePath . '/attraction' => $basePath . '/attractions',
            $basePath . '/resort' => $basePath . '/resorts',
            $basePath . '/market' => $basePath . '/markets',
            $basePath . '/product' => $basePath . '/products',
            $basePath . '/local-product' => $basePath . '/products',
            $basePath . '/local-products' => $basePath . '/products',
        ];

        return $aliases[$path] ?? $path;
    }
}

if (!function_exists('jt_is_explore_section_path')) {
    function jt_is_explore_section_path(string $normalizedPath, string $baseUrl): bool
    {
        $basePath = rtrim($baseUrl, '/');

        return in_array($normalizedPath, [
            $basePath . '/explore',
            $basePath . '/attractions',
            $basePath . '/resorts',
            $basePath . '/products',
            $basePath . '/markets',
        ], true);
    }
}
