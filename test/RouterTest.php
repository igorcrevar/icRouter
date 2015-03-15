<?php

namespace IgorCrevar\icRouter\test;

use IgorCrevar\icRouter\Router;
use IgorCrevar\icRouter\Route;
use IgorCrevar\icRouter\Interfaces\DefImpl\DefaultNodeBuilder;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    private $router;
    
    protected function setUp()
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
    
    function arrays_are_similar($a, $b) {
        // if the indexes don't match, return immediately
        if (count(array_diff_assoc($a, $b))) {
            return false;
        }
        // we know that the indexes, but maybe not values, match.
        // compare the values between the two arrays
        foreach($a as $k => $v) {
            if ($v !== $b[$k]) {
                return false;
            }
        }
        // we have identical indexes, and no unequal values
        return true;
    }
    
    public function testHomeMatch()
    {
        $result = $this->router->match('/a/b/c/d/e');
        $this->assertTrue($this->arrays_are_similar($result, 
            array('module' => 'home', 'a' => 'b', 'c' => 'd')));
        
        $result = $this->router->match('/kobac/2');
        $this->assertTrue($this->arrays_are_similar($result, 
            array('module' => 'home', 'kobac' => '2')));
    }
    
    public function testSimpleMatch()
    {
        $result = $this->router->match('/simple');
        $this->assertTrue($this->arrays_are_similar($result, 
            array('module' => 'simple', 'a' => 10)));
    }
    
    public function testSimpleParamMatch()
    {
        $result = $this->router->match('/param/20');
        $this->assertTrue($this->arrays_are_similar($result, 
                          array('module' => 'simple_param', 'a' => '20')));
        $result = $this->router->match('/param/dzuvec');
        $this->assertFalse($result);
    }
    
    public function testComplexParamMatch() {
        $result = $this->router->match('/complex/id_125');
        $this->assertTrue($this->arrays_are_similar($result,
            array('module' => 'complex_param', 'id' => '125')));
        $result = $this->router->match('/complex/ide_125');
        $this->assertFalse($result);
        $result = $this->router->match('/complex/125');
        $this->assertFalse($result);
    }
    
    public function testTwoParamsMatch()
    {
        $result = $this->router->match('/param/hello/two/some/qw');
        $this->assertTrue($this->arrays_are_similar($result, 
                          array('module' => 'two_params', 'a' => 'two', 
                                'onemore' => 'time', 'b' => 'qw')));
        $result = $this->router->match('/param/hello/ko/some');
        $this->assertFalse($result);
    }
    
    public function testTwoParamsAnyMatch()
    {
        $result = $this->router->match('/home/hello/1/01/c/3/d');
        $this->assertTrue($this->arrays_are_similar($result,
            array('module' => 'two_params_any', 'a' => '1', 'b' => '01', 'c' => '3')));
        // b is not [01]+
        $result = $this->router->match('/home/hello/1/201/c/3/d');
        $this->assertFalse($result);
        
        $result = $this->router->match('/home/hello/1/01');
        $this->assertTrue($this->arrays_are_similar($result,
            array('module' => 'two_params_any', 'a' => '1', 'b' => '01')));
        
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
     * @expectedException IgorCrevar\icRouter\RouterException
     */
    public function testGenerateExceptionParamNotSet()
    {
        // b is not set
        $this->router->generate('two_params');
    }
    
    public function testGenerateComplexParam() {
        $result = $this->router->generate('complex_param', array('id' => 1));
        $this->assertEquals('/complex/id_1', $result);
    }
    
    
    /**
     * @expectedException IgorCrevar\icRouter\RouterException
     */
    public function testGenerateExceptionAdditionalParamoOnNonStarRoute()
    {
        // additional params set and not end with *
        $this->router->generate('two_params', array('b' => 1, 'c' => 2));
    }
}
    
spl_autoload_register(function($className) {
    if (strpos($className, 'IgorCrevar\\icRouter\\') === 0) {
        $path = dirname(__FILE__).'\\..\\src\\';
        include str_replace('\\', DIRECTORY_SEPARATOR, $path.$className).'.php';
    }
});
