<?php
namespace PathForge\icRouter\Interfaces\DefImpl;

use PathForge\icRouter\Interfaces\INodeBuilder;
use PathForge\icRouter\Route;
use PathForge\icRouter\RouterException;
use PathForge\icRouter\Nodes\ARouteNode;
use PathForge\icRouter\Nodes\RouteNodeLeafAny;
use PathForge\icRouter\Nodes\RouteNodeLeaf;
use PathForge\icRouter\Nodes\RouteNode;
use PathForge\icRouter\Nodes\RouteNodeParam;
use PathForge\icRouter\Nodes\RouteNodeRoot;
use PathForge\icRouter\Nodes\RouteNodeParamSimple;

class DefaultNodeBuilder implements INodeBuilder 
{
    public function build($value, Route $route, ARouteNode $parent) 
    {
        if ($value === '*') {
            return new RouteNodeLeafAny($parent, $route->getDefaults());
        }
        else if ($value === '') {
            return new RouteNodeLeaf($parent, $route->getDefaults());
        }
        else if (strpos($value, ':') === false) {
            return new RouteNode($parent, $value);
        }
        else {
            $matches = array();
            preg_match('/:(?<ident>[A-Za-z0-9]+)/', $value, $matches);
            if (!isset($matches['ident'])) {
                throw new RouterException("Invalid parameter: ".$value);
            }
        
            $ident = $matches['ident'];
            $pattern = $route->getParamPattern($ident);
            if ($pattern !== false) {
                return new RouteNodeParam($parent, $value, $ident, $pattern);
            }
            else {
                return new RouteNodeParamSimple($parent, $ident);
            }
        }
    }
    
    public function buildRoot() 
    {
        return new RouteNodeRoot();
    }
}