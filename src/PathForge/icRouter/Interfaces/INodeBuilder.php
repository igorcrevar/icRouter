<?php

namespace PathForge\icRouter\Interfaces;

use PathForge\icRouter\Route;
use PathForge\icRouter\Nodes\ARouteNode;

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