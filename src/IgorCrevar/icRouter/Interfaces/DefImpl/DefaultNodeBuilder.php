<?php
namespace IgorCrevar\icRouter\Interfaces\DefImpl;

use IgorCrevar\icRouter\Interfaces\INodeBuilder;
use IgorCrevar\icRouter\Route;
use IgorCrevar\icRouter\Nodes\ARouteNode;
use IgorCrevar\icRouter\Nodes\RouteNodeLeafAny;
use IgorCrevar\icRouter\Nodes\RouteNodeLeaf;
use IgorCrevar\icRouter\Nodes\RouteNode;
use IgorCrevar\icRouter\Nodes\RouteNodeParam;
use IgorCrevar\icRouter\Nodes\RouteNodeRoot;
use IgorCrevar\icRouter\Nodes\RouteNodeParamSimple;

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