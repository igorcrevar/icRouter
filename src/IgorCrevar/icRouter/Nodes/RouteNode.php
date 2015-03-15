<?php 

namespace IgorCrevar\icRouter\Nodes;

class RouteNode extends ARouteNode
{
    private $value;

    public function __construct(ARouteNode $parent, $value)
    {
        parent::__construct();
        $this->setParent($parent);
        $this->value = $value;
    }

    public function isMatch($value)
    {
        return $value === $this->value;
    }

    public function processStep(&$values, &$params, $index)
    {
        return $index + 1;
    }

    public function getString(&$params, $delimiter, &$defaults) 
    {
        return $this->value;
    }
}
