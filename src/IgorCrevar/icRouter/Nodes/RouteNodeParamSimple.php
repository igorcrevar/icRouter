<?php

namespace IgorCrevar\icRouter\Nodes;

use IgorCrevar\icRouter\RouterException;

class RouteNodeParamSimple extends ARouteNode
{
    private $identifier;
    
    public function __construct(ARouteNode $parent, $identifier)
    {
        parent::__construct();
        $this->setParent($parent);
        
        $this->identifier = $identifier;
    }

    public function isMatch($value)
    {
       return true;
    }

    public function processStep(&$values, &$params, $index)
    {
        $params[$this->identifier] = $values[$index];
        return $index + 1;
    }

    public function getString(&$params, $delimiter, &$defaults)
    {
        $value = $this->getValue($params, $defaults, $this->identifier);
        // parameter is used, so we need to unset it @see RouteParamLeafAny
        unset($params[$this->identifier]);
        return $value;
    }
}
