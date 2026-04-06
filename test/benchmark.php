<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

use IgorCrevar\icRouter\Router;
use IgorCrevar\icRouter\Route;
use IgorCrevar\icRouter\Interfaces\DefImpl\DefaultNodeBuilder;

function formatNumber($n) {
    return number_format($n, 0, '.', ',');
}

function bench($label, $iterations, $callback) {
    // warmup
    for ($i = 0; $i < min(1000, $iterations); $i++) {
        $callback();
    }

    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $callback();
    }
    $elapsed = (hrtime(true) - $start) / 1e9;

    $opsPerSec = $iterations / $elapsed;
    printf("  %-50s %s ops/sec  (%.4fs for %s iterations)\n",
        $label, formatNumber((int)$opsPerSec), $elapsed, formatNumber($iterations));
}

// ---------------------------------------------------------------------------
// Setup: small route set (7 routes, same as unit tests)
// ---------------------------------------------------------------------------
function buildSmallRouter() {
    $router = new Router(new DefaultNodeBuilder());
    $router->setRoutes([
        new Route('simple', '/simple',
                  array('module' => 'simple')),
        new Route('simple_param', '/param/:a',
                  array('module' => 'simple_param', 'a' => 10),
                  array('a' => '\d+')),
        new Route('two_params', '/param/hello/:a/some/:b',
                  array('module' => 'two_params', 'a' => 10, 'onemore' => 'time')),
        new Route('two_params_any', '/home/hello/:a/:b/*',
                  array('module' => 'two_params_any', 'a' => 10, 'b' => '10'),
                  array('b' => '[01]+')),
        new Route('complex_param', '/complex/id_:id',
                  array('module' => 'complex_param'),
                  array('id' => '\d+')),
        new Route('labud', '/labud/:a/*',
                  array('module' => 'labud', 'a' => 10, 'b' => 20)),
        new Route('home', '/*',
                  array('module' => 'home')),
    ]);
    $router->build();
    return $router;
}

// ---------------------------------------------------------------------------
// Setup: large route set (500 routes simulating a real application)
// ---------------------------------------------------------------------------
function buildLargeRouter() {
    $router = new Router(new DefaultNodeBuilder());
    $routes = [];

    $resources = [
        'users', 'posts', 'comments', 'categories', 'tags',
        'products', 'orders', 'invoices', 'payments', 'reviews',
        'articles', 'pages', 'media', 'settings', 'notifications',
        'messages', 'groups', 'roles', 'permissions', 'logs',
    ];

    $actions = ['list', 'create', 'show', 'edit', 'delete',
                'archive', 'restore', 'export', 'import', 'stats'];

    $i = 0;
    foreach ($resources as $resource) {
        // /api/v1/{resource}
        $routes[] = new Route(
            "{$resource}_list", "/api/v1/{$resource}",
            array('module' => $resource, 'action' => 'list')
        );
        // /api/v1/{resource}/:id
        $routes[] = new Route(
            "{$resource}_show", "/api/v1/{$resource}/:id",
            array('module' => $resource, 'action' => 'show'),
            array('id' => '\d+')
        );
        // /api/v1/{resource}/:id/{action}
        foreach ($actions as $action) {
            $routes[] = new Route(
                "{$resource}_{$action}_{$i}", "/api/v1/{$resource}/:id/{$action}",
                array('module' => $resource, 'action' => $action),
                array('id' => '\d+')
            );
            $i++;
        }
        // /api/v1/{resource}/:id/related/:relId
        $routes[] = new Route(
            "{$resource}_related", "/api/v1/{$resource}/:id/related/:relId",
            array('module' => $resource, 'action' => 'related'),
            array('id' => '\d+', 'relId' => '\d+')
        );
        // /api/v2/{resource}/*
        $routes[] = new Route(
            "{$resource}_v2", "/api/v2/{$resource}/*",
            array('module' => $resource, 'version' => 2)
        );
    }

    // catchall
    $routes[] = new Route('catchall', '/*', array('module' => 'fallback'));

    $router->setRoutes($routes);
    $router->build();
    return $router;
}

// ---------------------------------------------------------------------------
// Run benchmarks
// ---------------------------------------------------------------------------
$iterations = 100000;

echo "=== Build ===\n";
bench("Small router (7 routes)", 10000, function() {
    buildSmallRouter();
});
bench("Large router (500+ routes)", 1000, function() {
    buildLargeRouter();
});

echo "\n=== Match: Small router (7 routes) ===\n";
$small = buildSmallRouter();

bench("Static route (first)", $iterations, function() use ($small) {
    $small->match('/simple');
});
bench("Param with regex constraint", $iterations, function() use ($small) {
    $small->match('/param/42');
});
bench("Param regex miss (fallback to later route)", $iterations, function() use ($small) {
    $small->match('/param/hello');
});
bench("Two params, deep path", $iterations, function() use ($small) {
    $small->match('/param/hello/foo/some/bar');
});
bench("Complex embedded param (id_:id)", $iterations, function() use ($small) {
    $small->match('/complex/id_999');
});
bench("Wildcard with extra key/value pairs", $iterations, function() use ($small) {
    $small->match('/home/hello/1/01/color/red/size/large');
});
bench("Catchall wildcard (/*)", $iterations, function() use ($small) {
    $small->match('/anything/goes/here');
});
bench("No match (false)", $iterations, function() use ($small) {
    $small->match('/param/hello/foo/notsome/bar');
});

echo "\n=== Match: Large router (500+ routes) ===\n";
$large = buildLargeRouter();

bench("First resource, list", $iterations, function() use ($large) {
    $large->match('/api/v1/users');
});
bench("First resource, show by id", $iterations, function() use ($large) {
    $large->match('/api/v1/users/123');
});
bench("First resource, action", $iterations, function() use ($large) {
    $large->match('/api/v1/users/123/edit');
});
bench("Middle resource (orders), show", $iterations, function() use ($large) {
    $large->match('/api/v1/orders/456');
});
bench("Middle resource, action", $iterations, function() use ($large) {
    $large->match('/api/v1/orders/456/stats');
});
bench("Last resource (logs), show", $iterations, function() use ($large) {
    $large->match('/api/v1/logs/789');
});
bench("Last resource, deep related", $iterations, function() use ($large) {
    $large->match('/api/v1/logs/789/related/42');
});
bench("V2 wildcard with params", $iterations, function() use ($large) {
    $large->match('/api/v2/products/color/red/page/3');
});
bench("Catchall (no match in tree)", $iterations, function() use ($large) {
    $large->match('/unknown/path/here');
});

echo "\n=== Generate: Small router ===\n";
bench("Static route", $iterations, function() use ($small) {
    $small->generate('simple');
});
bench("Single param with default", $iterations, function() use ($small) {
    $small->generate('simple_param', array('a' => 42));
});
bench("Two params", $iterations, function() use ($small) {
    $small->generate('two_params', array('a' => 'foo', 'b' => 'bar'));
});
bench("Wildcard with extra params", $iterations, function() use ($small) {
    $small->generate('two_params_any', array('a' => 1, 'c' => 2, 'd' => 3));
});

echo "\n=== Generate: Large router ===\n";
bench("First resource, show", $iterations, function() use ($large) {
    $large->generate('users_show', array('id' => 123));
});
bench("Middle resource, show", $iterations, function() use ($large) {
    $large->generate('orders_show', array('id' => 456));
});
bench("Last resource, related", $iterations, function() use ($large) {
    $large->generate('logs_related', array('id' => 789, 'relId' => 42));
});
bench("V2 wildcard", $iterations, function() use ($large) {
    $large->generate('products_v2', array('color' => 'red', 'page' => 3));
});

echo "\n=== Memory ===\n";
$before = memory_get_usage();
$fresh = buildLargeRouter();
$after = memory_get_usage();
printf("  Large router memory footprint: %s KB\n", number_format(($after - $before) / 1024, 1));
printf("  Peak memory usage: %s KB\n", number_format(memory_get_peak_usage() / 1024, 1));
