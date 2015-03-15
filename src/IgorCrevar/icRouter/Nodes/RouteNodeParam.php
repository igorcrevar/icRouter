<?php

namespace IgorCrevar\icRouter\Nodes;

use IgorCrevar\icRouter\RouterException;

class RouteNodeParam extends ARouteNode
{
    private $identifier;
    private $value;
    private $fullPattern;
    private $lastMatchedParam;
    private $identifierWithPrefix;
    
    public function __construct(ARouteNode $parent, $value, $identifier, $pattern)
    {
        parent::__construct();
        $this->setParent($parent);
        
        $this->identifier = $identifier;
        $this->identifierWithPrefix = ':'.$this->identifier;
        $tmp = str_replace(':'.$identifier,
                           '(?<ident>'.$pattern.')',
                           $value);
        $this->fullPattern = '/^'.$tmp.'$/';
        $this->value = $value;
    }

    public function isMatch($value)
    {
       $matches = array();
       preg_match($this->fullPattern, $value, $matches);
       if (!isset($matches['ident'])) {
           return false;
       }
       
       $this->lastMatchedParam = $matches['ident'];
       return true;
    }

    public function processStep(&$values, &$params, $index)
    {
        $params[$this->identifier] = $this->lastMatchedParam;
        return $index + 1;
    }

    public function getString(&$params, $delimiter, &$defaults)
    {
        $value = str_replace($this->identifierWithPrefix,
                             $this->getValue($params, $defaults, $this->identifier),
                             $this->value);
        if (!$this->isMatch($value)) {
            throw new RouterException(
                sprintf("ident: `%s` parameter not match `%s` pattern %s",
                $this->identifier, $value, $this->fullPattern));
        }

        // parameter is used, so we need to unset it @see RouteParamLeafAny
        unset($params[$this->identifier]);
        return $value;
    }
}
