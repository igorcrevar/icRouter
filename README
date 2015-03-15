#icRouter 
Different approach for standard routing problem. Instead of using regular expressions, matching tree is built.

This library will not work with older versions of PHP. Minimal version is 5.3

## Usage
#### Usings
``` php
use IgorCrevar\icRouter\Router;
use IgorCrevar\icRouter\Route;
use IgorCrevar\icRouter\Interfaces\DefImpl\DefaultNodeBuilder;
```
#### Create router
``` php
$router = new Router(new DefaultNodeBuilder());
```
#### Add some routes
``` php
$router->setRoutes([
    new Route('simple', '/simple', 
              array('module' => 'simple')),
    new Route('simple_param', '/param/:a', 
              array('module' => 'simple_param', 'a' => 10), 
              array('a' => '\d+')), // a is integer
    new Route('two_params', '/param/hello/:a/some/:b', 
              array('module' => 'two_params', 'a' => 10, 'onemore' => 'time')),
    new Route('two_params_any', '/home/hello/:a/:b/*', 
              array('module' => 'two_params_any', 'a' => 10, 'b' => '10'),
              array('b' => '[01]+')), // b is string / number of 0' and 1'
    new Route('complex_param', '/complex/id_:id',
              array('module' => 'complex_param'),
              array('id' => '\d+')),
    new Route('home', '/*', 
              array('module' => 'home')),
]);
```
#### Build route tree
``` php
$router->build();
```
#### Match
``` php
$result = $router->match('/a/b/c/d/e');
```
$result will be array of matching parameters (key value pairs) if route exists otherwise false is returned
#### Generate
``` php
$result = $router->generate('two_params', array('b' => 'aabb'));
```
First parameter is route name, second is parameters (key value pairs) array

#### Route constructor parameters
- name of route
- patterns:
	Examples:
	1. /acount/:id/*  - provides functionality for additional parameters
	2. /account/:id/:action    
    3. /account/id_:id
    Parameter is specified with /:[A-Za-z0-9]+/
    Only one parameter is allowed per route segment
- optional default parameters for route (key - value pairs)
- optional regex for parameters in pattern
	Examples:
	1. array('id' => '\d+')  - id is integer
	2. array('type' => 'car|boat|plane') - type is either car or boat or plane

## Tip
For production, because $router->build() is expensive 
you should cache already built router (APC, serializing, etc...)

## TODO
perfomance banchmark beetween this library and some  
regular expression routing library like one from symfony framework or similar.

 