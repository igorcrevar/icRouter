# PathForge Router

A tree-based URL router for PHP. Instead of iterating over a list of regular expressions, PathForge Router builds a matching tree from registered routes, providing efficient O(depth) lookups.

Requires PHP 5.5 or later.

## Installation

```
composer require pathforge/icrouter
```

## Usage

### Setup

```php
use PathForge\icRouter\Router;
use PathForge\icRouter\Route;
use PathForge\icRouter\Interfaces\DefImpl\DefaultNodeBuilder;

$router = new Router(new DefaultNodeBuilder());
```

### Define routes

```php
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
    new Route('home', '/*',
              array('module' => 'home')),
]);

$router->build();
```

### Match a URL

```php
$result = $router->match('/param/20');
// Returns: array('module' => 'simple_param', 'a' => '20')

$result = $router->match('/nonexistent');
// Returns: false
```

`match()` returns an associative array of parameters on success, or `false` if no route matches.

### Generate a URL

```php
$result = $router->generate('two_params', array('b' => 'aabb'));
// Returns: '/param/hello/10/some/aabb'
```

The first argument is the route name, the second is an array of parameters. Missing parameters are filled from defaults.

## Route constructor

```php
new Route($name, $pattern, $defaults = array(), $parameters = array())
```

| Argument | Description |
|---|---|
| `$name` | Unique route name, used for URL generation |
| `$pattern` | URL pattern with optional parameters and wildcard |
| `$defaults` | Default values for parameters (key-value pairs) |
| `$parameters` | Regex constraints for named parameters |

### Pattern syntax

- Static segments match literally: `/account/list`
- Named parameters are prefixed with `:` and match a single segment: `/account/:id`
- Named parameters can be embedded in a segment: `/account/id_:id`
- A trailing `*` captures remaining segments as key-value pairs: `/account/:id/*`
- Parameter names must match `[A-Za-z0-9]+`
- Only one named parameter is allowed per segment

### Parameter constraints

Regex patterns (without delimiters) can be specified per parameter:

```php
array('id' => '\d+')            // id must be an integer
array('type' => 'car|boat|plane') // type must be one of these values
```

## Route ordering

Routes are matched in registration order. When multiple routes could match a URL, the **first registered route wins**. Place more specific routes before general ones:

```php
$router->setRoutes([
    new Route('specific', '/param/:a', ...),  // checked first
    new Route('catchall', '/*', ...),          // fallback
]);
```

## HTTP methods

icRouter matches URL paths only and does not handle HTTP methods (GET, POST, PUT, DELETE, etc.). Method dispatch can be handled by your application code, for example by including the method in the route defaults:

```php
$router->setRoutes([
    new Route('create_user', '/user/create',
              array('module' => 'user', 'action' => 'create', 'method' => 'POST')),
]);

$result = $router->match('/user/create');
if ($result && $result['method'] !== $_SERVER['REQUEST_METHOD']) {
    // return 405 Method Not Allowed
}
```

## Performance tip

`$router->build()` constructs the matching tree and is relatively expensive. In production, cache the built router instance (e.g. via `serialize()`, APC, or similar) to avoid rebuilding on every request.

## Unit testing

```
vendor/bin/phpunit test/RouterTest.php
```

## Benchmarks

Run benchmarks:

```
php test/benchmark.php
```

Results on PHP 8.1 (100,000 iterations per test):

### Match performance

| Scenario | Small router (7 routes) | Large router (500+ routes) |
|---|---|---|
| Static route | ~695K ops/sec | ~386K ops/sec |
| Param with regex | ~324K ops/sec | ~242K ops/sec |
| Two params, deep path | ~175K ops/sec | ~167K ops/sec |
| Wildcard with key/value pairs | ~170K ops/sec | ~242K ops/sec |
| Catchall (`/*`) | ~466K ops/sec | ~599K ops/sec |
| No match | ~313K ops/sec | — |

### Generate performance

| Scenario | Small router | Large router |
|---|---|---|
| Static route | ~1.3M ops/sec | — |
| Single param | ~468K ops/sec | ~408K ops/sec |
| Two params | ~516K ops/sec | ~244K ops/sec |
| Wildcard with params | ~317K ops/sec | ~592K ops/sec |

### Build and memory

| Metric | Small (7 routes) | Large (500+ routes) |
|---|---|---|
| Build speed | ~21K ops/sec | ~327 ops/sec |
| Memory footprint | — | ~744 KB |

The tree structure scales well: matching with 500+ routes is only 30–50% slower than with 7 routes, since lookup depends on tree depth rather than total route count. At ~170K+ ops/sec in the worst case, routing takes roughly 6 microseconds per request.