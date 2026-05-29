<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$base = '/jasaan-tourism';


$request = $_SERVER['REQUEST_URI'];
$request = strtok($request, '?');
$request = str_replace($base, '', $request);
$request = rtrim($request, '/');

if ($request === '') {
    $request = '/';
}


$routes = [


    '/' => 'frontend/pages/user/user_explore.php',
    '/explore' => 'frontend/pages/user/user_explore.php',
    '/attraction' => 'frontend/pages/user/attractions.php',
    '/attractions' => 'frontend/pages/user/attractions.php',
    '/resort' => 'frontend/pages/user/resorts.php',
    '/resorts' => 'frontend/pages/user/resorts.php',
    '/market' => 'frontend/pages/user/markets.php',
    '/markets' => 'frontend/pages/user/markets.php',
    '/product' => 'frontend/pages/user/local_products.php',
    '/local-product' => 'frontend/pages/user/local_products.php',
    '/local-products' => 'frontend/pages/user/local_products.php',
    '/products' => 'frontend/pages/user/local_products.php',


    '/admin' => 'frontend/pages/admin/admin_dashboard.php',

];


if (array_key_exists($request, $routes)) {

    $page = $routes[$request];


    if ($request === '/admin') {
        require 'backend/check_admin.php';
    }

    require $page;
} else {
    http_response_code(404);
    require 'frontend/pages/404.php';
}
