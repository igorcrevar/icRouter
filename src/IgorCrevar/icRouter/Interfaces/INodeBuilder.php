<?php

namespace IgorCrevar\icRouter\Interfaces;

use IgorCrevar\icRouter\Route;
use IgorCrevar\icRouter\Nodes\ARouteNode;

interface INodeBuilder {
   
    /**
     * Build node
     * @param string $value
     * @param Route $route
     * @param ARouteNode $parent
     */
    public function build($value, Route $route, ARouteNode $parent);
    
    /**
     * Build root node
     * @return ARouteNode
     */
    public function buildRoot();
}