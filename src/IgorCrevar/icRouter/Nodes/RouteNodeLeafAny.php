<?php

namespace IgorCrevar\icRouter\Nodes;

class RouteNodeLeafAny extends RouteNodeLeaf
{
    public function __construct(ARouteNode $parent, $defaults)
    {
        parent::__construct($parent, $defaults);
    }

    public function processStep(&$values, &$params, $index)
    {
        $cnt = count($values);
        for ($i = $index; $i + 1 < $cnt; $i += 2) {
            $params[$values[$i]] = $values[$i + 1];
        }

        parent::processStep($values, $params, $index);
        return $cnt;
    }

    public function getString(&$params, $delimiter, &$defaults)
    {
        $str = '';
        foreach ($params as $key => $val) {
            if (isset($defaults[$key]) && $defaults[$key] === $val) {
                continue;
            }
            $str = $str . $delimiter . $key . $delimiter . $val;
        }
        return strlen($str) > 0 ? substr($str, 1) : '';
    }

    public function isMatch($value)
    {
        return true;
    }
}