<?php 

namespace IgorCrevar\icRouter\Nodes;

class RouteNodeRoot extends ARouteNode
{
    public function __construct()
    {
        parent::__construct();
    }

    public function isMatch($value)
    {
        return true;
    }

    public function processStep(&$values, &$params, $index)
    {
        return $index;
    }

    public function getString(&$params, $delimiter, &$defaults)
    {
        return '';
    }
}
