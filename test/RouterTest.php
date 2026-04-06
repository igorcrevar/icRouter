<?php

namespace PathForge\icRouter\test;

use PathForge\icRouter\Router;
use PathForge\icRouter\Route;
use PathForge\icRouter\Interfaces\DefImpl\DefaultNodeBuilder;

class RouterTest extends \PHPUnit\Framework\TestCase
{
    private $router;
    
    protected function setUp(): void
    {
        $this->router = new Router(new DefaultNodeBuilder());
        
        $this->router->setRoutes([
            new Route('simple', '/simple', 
                      array('module' => 'simple')),
            new Route('simple_param', '/param/:a', 
                      array('module' => 'simple_param', 'a' => 10), 
                      array('a' => '\d+')), ///^\d+$/')),
            new Route('two_params', '/param/hello/:a/some/:b', 
                      array('module' => 'two_params', 'a' => 10, 'onemore' => 'time')),
            new Route('two_params_any', '/home/hello/:a/:b/*', 
                      array('module' => 'two_params_any', 'a' => 10, 'b' => '10'),
                      //array('b' => '/^[01]+$/')),
                      array('b' => '[01]+')), ///^\d+$/')),
            new Route('labud', '/labud/:a/*', 
                      array('module' => 'labud', 'a' => 10, 'b' => 20)),
            new Route('complex_param', '/complex/id_:id',
                array('module' => 'complex_param'),
                array('id' => '\d+')), ///^\d+$/')),
            new Route('home', '/*', 
                      array('module' => 'home')),
        ]);
        
        // must build tree
        $this->router->build();
    }
    
    public function testHomeMatch()
    {
        $result = $this->router->match('/a/b/c/d/e');
        $this->assertEquals(
            array('module' => 'home', 'a' => 'b', 'c' => 'd'), $result);
        
        $result = $this->router->match('/kobac/2');
        $this->assertEquals(
            array('module' => 'home', 'kobac' => '2'), $result);
    }
    
    public function testSimpleMatch()
    {
        $result = $this->router->match('/simple');
        $this->assertEquals(
            array('module' => 'simple'), $result);
    }
    
    public function testSimpleParamMatch()
    {
        $result = $this->router->match('/param/20');
        $this->assertEquals(
            array('module' => 'simple_param', 'a' => '20'), $result);
        $result = $this->router->match('/param/dzuvec');
        $this->assertFalse($result);
    }
    
    public function testComplexParamMatch() {
        $result = $this->router->match('/complex/id_125');
        $this->assertEquals(
            array('module' => 'complex_param', 'id' => '125'), $result);
        $result = $this->router->match('/complex/ide_125');
        $this->assertFalse($result);
        $result = $this->router->match('/complex/125');
        $this->assertFalse($result);
    }
    
    public function testTwoParamsMatch()
    {
        $result = $this->router->match('/param/hello/two/some/qw');
        $this->assertEquals(
            array('module' => 'two_params', 'a' => 'two', 
                  'onemore' => 'time', 'b' => 'qw'), $result);
        $result = $this->router->match('/param/hello/ko/some');
        $this->assertFalse($result);
    }
    
    public function testTwoParamsAnyMatch()
    {
        $result = $this->router->match('/home/hello/1/01/c/3/d');
        $this->assertEquals(
            array('module' => 'two_params_any', 'a' => '1', 'b' => '01', 'c' => '3'), $result);
        // b is not [01]+
        $result = $this->router->match('/home/hello/1/201/c/3/d');
        $this->assertFalse($result);
        
        $result = $this->router->match('/home/hello/1/01');
        $this->assertEquals(
            array('module' => 'two_params_any', 'a' => '1', 'b' => '01'), $result);
        
    }
    
    public function testGenerateTwoParamsAny()
    {
        $result = $this->router->generate('two_params_any', 
            array('a' => 20, 'c' => 1));
        $this->assertEquals('/home/hello/20/10/c/1', $result);
        
        $result = $this->router->generate('two_params_any', array('a' => 20));
        $this->assertEquals('/home/hello/20/10', $result);
        $result = $this->router->generate('two_params_any', array());
        $this->assertEquals('/home/hello/10/10', $result);
    }
    
    public function testGenerateHome()
    {
        $result = $this->router->generate('home', array('a' => 20, 'c' => 1));
        $this->assertEquals('/a/20/c/1', $result);
    }
    
    public function testGenerateTwoParams() 
    {
        $result = $this->router->generate('two_params', array('b' => 'aabb'));
        $this->assertEquals('/param/hello/10/some/aabb', $result);
        $result = $this->router->generate('two_params', 
                  array('b' => 'aabb', 'a' => 'bbaa'));
        $this->assertEquals('/param/hello/bbaa/some/aabb', $result);
    }
    
    /**
     * b is not set
     */
    public function testGenerateExceptionParamNotSet()
    {
        $this->expectException(\PathForge\icRouter\RouterException::class);
        // b is not set
        $this->router->generate('two_params');
    }
    
    public function testGenerateComplexParam() {
        $result = $this->router->generate('complex_param', array('id' => 1));
        $this->assertEquals('/complex/id_1', $result);
    }
    
    
    /**
     * additional params set and not end with *
     */
    public function testGenerateExceptionAdditionalParamoOnNonStarRoute()
    {
        $this->expectException(\PathForge\icRouter\RouterException::class);
        // additional params set and not end with *
        $this->router->generate('two_params', array('b' => 1, 'c' => 2));
    }

    /**
     * Wildcard segments can legitimately overwrite defaults.
     */
    public function testWildcardCanOverwriteDefaults()
    {
        // The 'home' route has default module => 'home'
        // Wildcard segments should be able to override defaults
        $result = $this->router->match('/module/other');
        $this->assertIsArray($result);
        $this->assertEquals('other', $result['module']);
    }

    /**
     * Wildcard segments cannot overwrite previously matched named params.
     */
    public function testWildcardCannotOverwriteNamedParams()
    {
        // The 'two_params_any' route: /home/hello/:a/:b/*
        // URL tries to overwrite 'a' via the wildcard segments
        $result = $this->router->match('/home/hello/1/01/a/overwritten');
        $this->assertIsArray($result);
        // 'a' should remain '1' from the :a param, not overwritten
        $this->assertEquals('1', $result['a']);
    }

    /**
     * ReDoS: a malicious regex pattern causes catastrophic backtracking.
     * After fix, this throws RouterException instead of silently returning false.
     */
    public function testReDoSWithMaliciousPattern()
    {
        $router = new Router(new DefaultNodeBuilder());
        $router->setRoutes([
            new Route('redos', '/redos/:input', 
                      array('module' => 'redos'),
                      array('input' => '(a+)+')),
        ]);
        $router->build();
        
        $this->expectException(\PathForge\icRouter\RouterException::class);
        $router->match('/redos/' . str_repeat('a', 25) . '!');
    }

    /**
     * Missing use statement: DefaultNodeBuilder throws RouterException without importing it.
     * A route pattern with an invalid parameter syntax (colon but no valid identifier)
     * should throw RouterException, but instead causes a fatal "class not found" error.
     */
    public function testMissingUseStatementInDefaultNodeBuilder()
    {
        $router = new Router(new DefaultNodeBuilder());
        $router->setRoutes([
            // ':' with no valid identifier after it triggers the exception path
            new Route('bad_param', '/bad/:',
                      array('module' => 'bad')),
        ]);
        
        // This should throw RouterException but will fail with
        // "Class 'PathForge\icRouter\Interfaces\DefImpl\RouterException' not found"
        $this->expectException(\PathForge\icRouter\RouterException::class);
        $router->build();
    }
}
    
spl_autoload_register(function($className) {
    if (strpos($className, 'PathForge\\icRouter\\') === 0) {
        $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
        include str_replace('\\', DIRECTORY_SEPARATOR, $path.$className).'.php';
    }
});
