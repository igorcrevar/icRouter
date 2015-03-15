<?php

namespace IgorCrevar\icRouter;

use IgorCrevar\icRouter\Interfaces\INodeBuilder;

/**
 * Router class, dealing with matching and generating routes
 * @author igor
 *
 */
class Router
{
    /**
     * @var Pattern of route for example /account/:id
     */
    protected $routes = array();
    /**
     * @var route name => array of defaults + array of Nodes
     */
    protected $nameMap = array();
    
    /**
     * @var root node
     */
    protected $rootNode;
    
    /**
     * @var INodeBuilder instance
     */
    protected $nodeBuilder;
    
    /**
     * @var string default is '/'
     */
    protected $delimiter;
    
    /**
     * Constructor
     */
    public function __construct(INodeBuilder $nodeBuilder, $delimiter = '/')
    {
        $this->nodeBuilder = $nodeBuilder;
        $this->delimiter = $delimiter;
    }
    
    /**
     * Add new route
     * @param Route $route
     */
    public function addRoute(Route $route)
    {
        $this->routes[] = $route;
    }
    
    /**
     * Set array of Route instances
     * @param array $routes
     */
    public function setRoutes($routes = array())
    {
        $this->routes = $routes;
    }
    
    /**
     * Build route tree and name map
     * @throws RouterException
     */
    public function build()
    {
        $this->rootNode = $this->nodeBuilder->buildRoot();
        foreach ($this->routes as $route) {
            $values = $this->explode($route->getPattern());
            $leafNodeSymbol = '';
            if ($values[count($values) - 1] == '*') {
                $leafNodeSymbol = '*';
                array_pop($values);
            }
            
            $nodesList = array();
            $currNode = $this->rootNode;
            foreach ($values as $value) {
                if ($value === '') {
                    throw new RouterException(sprintf('Route: %s is invalid', $route->getPattern()));
                }
                
                $node = $currNode->findMatchingChild($value);
                if ($node) {
                   $currNode = $node;
                }
                else {
                   $currNode = $this->nodeBuilder->build($value, $route, $currNode);
                }
                
                $nodesList[] = $currNode;
            }
            
            // build leaf node
            $currNode = $this->nodeBuilder->build($leafNodeSymbol, $route, $currNode);
            // //no nead for empty leaf node when generating
            //if ($leafNodeSymbol === '*') {
            $nodesList[] = $currNode;
            
            if ($route->getName()) {
                if (isset($this->nameMap[$route->getName()])) {
                    throw new RouterException(sprintf('Route name: %s already exists', $route->getName()));
                }
                $this->nameMap[$route->getName()] = array($route->getDefaults(), $nodesList);
            }
        }
    }
    
    /**
     * Tries to match string
     * @param string $str string to match
     * @return mixed array of key => value parameters | false
     */
    public function match($str)
    {
        $values = $this->explode($str);
        // 
        $result = array();
        $node = $this->rootNode;
        $i = 0;
        while ($i < count($values)) {
            $node = $node->findMatchingChild($values[$i]);
            if ($node == NULL) {
                return false;
            }
            
            $i = $node->processStep($values, $result, $i);
        }
        
        if ($node->isLeaf()) {
            return $result;
        }
        
        $node = $node->getLeaf();
        if ($node != NULL) {
            $node->processStep($values, $result, $i);
            return $result;
        }
        
        return false;
    }
    
    /**
     * Generate string for route
     * @param string $name route name
     * @param array $params route parameters
     * @throws RouterException
     * @return string route
     */
    public function generate($name, $params = array())
    {
        if (!isset($this->nameMap[$name])) {
            throw new RouterException(sprintf("Name: %s does not exists", $name));
        }
        
        $tmp = &$this->nameMap[$name];
        $defaults = &$tmp[0];
        $nodes = &$tmp[1];
        $tmpParams = $params; // shallow copy
        $str = '';
        foreach ($nodes as $node) {
            $str = $str.$this->delimiter.$node->getString($tmpParams, $this->delimiter, $defaults);
        }
        // because trailing / can occur
        return rtrim($str, $this->delimiter);
    }
    
    private function explode($str)
    {
        if (!is_string($str)) {
            throw new RouterException("Invalid parameter for explode");
        }
        
        if (strlen($str) > 0 && $str[0] === $this->delimiter) {
            $str = substr($str, 1);
        }
        
        return explode($this->delimiter, $str);
    }
}