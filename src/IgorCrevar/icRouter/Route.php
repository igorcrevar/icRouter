<?php

namespace IgorCrevar\icRouter;

/**
 * Describes route
 * @author igor
 *
 */
class Route
{
    /**
     * @var Pattern of route for example /account/:id
     */
    protected $pattern;
    /**
     * @var route name
     */
    protected $name;
    /**
     * @var Default parameter values
     */
    protected $defaults;
    /**
     * @var parameters paterns 
     */
    protected $parameters;
    
    /**
     * Constructor.
     */
    public function __construct(
        $name,
        $pattern, 
        $defaults = array(), 
        $parameters = array()
    ) {
        if (!is_string($pattern) || $pattern === '') {
            throw new RouterException("Invalid pattern");
        }
        
        if (!is_string($name) || $name === '') {
            throw new RouterException("Invalid Name");
        }
        
        $this->pattern = $pattern;
        $this->defaults = $defaults;
        $this->name = $name;
        $this->parameters = $parameters;
    }
    
    public function getPattern()
    {
        return $this->pattern;
    }
    
    public function getDefaults()
    {
        return $this->defaults;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getParamPattern($key)
    {
        return isset($this->parameters[$key]) ? $this->parameters[$key] : false;
    }
}