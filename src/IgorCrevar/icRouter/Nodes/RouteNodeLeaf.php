<?php

namespace IgorCrevar\icRouter\Nodes;

use IgorCrevar\icRouter\RouterException;
class RouteNodeLeaf extends ARouteNode
{
    protected $defaults;

    public function __construct(ARouteNode $parent, $defaults)
    {
        parent::__construct();
        $this->setParent($parent);
        $this->defaults = $defaults;
    }

    public function isMatch($value)
    {
        return $value === '';
    }

    public function processStep(&$values, &$params, $index)
    {
        foreach ($this->defaults as $key => $value) {
            if (!isset($params[$key])) {
                $params[$key] = $value;
            }
        }
        return $index;
    }

    public function isLeaf()
    {
        return true;
    }
    
    public function getString(&$params, $delimiter, &$defaults)
    {
        if (count($params) > 0) {
            throw new RouterException("Leaf node: There are some parameters left");
        }
        return '';
    }
}