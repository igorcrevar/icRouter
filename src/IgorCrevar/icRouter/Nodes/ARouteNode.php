<?php 

namespace IgorCrevar\icRouter\Nodes;

use IgorCrevar\icRouter\RouterException;

/**
 * Abstract route node of routing tree
 * @author igor
 *
 */
abstract class ARouteNode
{
    /**
     * @var array of ARouteNode
     */
    protected $children;
    
    /**
     * Constructor
     */
    public function __construct() 
    {
        $this->children = array();
    }
  
    
    /**
     * Check if node value matches some value
     * @param string $value
     * @return boolean true if node value matches input value
     */
    public abstract function isMatch($value);
    
    /**
     * Process $values at $index position
     * @param array string $values
     * @param array key->value $params. It is populated by nodes
     * @return new index for values
     */
    public abstract function processStep(&$values, &$params, $index);
    
    /**
     * Get string of node
     * @param $params array of key => map. Can be changed by method!
     * @param $delimiter string
     * @param $defaults array of key => map
     * @return string
     */
    public abstract function getString(&$params, $delimiter, &$defaults);
    
    
    /**
     * Is node leaf node?
     * @return bool true or false (default false)
     */
    public function isLeaf() 
    {
        return false;
    }
    
    /**
     * Iterate over children and find first one which matches value
     * @return ARouteNode if node is found or NULL
     */
    public function findMatchingChild($value) 
    {
        foreach ($this->children as $child) {
            if ($child->isMatch($value)) {
                return $child;
            }
        }
        return NULL;
    }
    
    /**
     * Yields all children
     * @return Generator ARouteNode
     */
    public function iterate() 
    {
        foreach ($this->children as $child) {
            yield $child;
        }
    }
    
    /**
     * Does not have any child
     * @return bool true of false 
     */
    public function hasChild() {
        return !empty($this->children);
    }
    
    /**
     * Does node has leaf (only one leaf should be allowed)
     * @return boolean
     */
    public function hasLeaf() 
    {
        return $this->getLeaf() !== NULL;
    }
    
    /**
     * Return leaf node if exist otherwise NULL
     * @return ARouteNode|NULL
     */
    public function getLeaf() 
    {
        foreach ($this->children as $child) {
            if ($child->isLeaf()) {
                return $child;
            }
        }
        return NULL;
    }
    
    /**
     * Set parent for node (update parent children also). Should be called only once in lifetime of node!
     * @param ARouteNode $parent
     * @throws RouterException
     */
    protected function setParent(ARouteNode $parent) 
    {
        // only one leaf per node should be allowed. Parent can not be leaf
        if ($parent->isLeaf()) {
            throw new RouterException("Parent can not be leaf");
        }
        else if ($this->isLeaf() && $parent->hasLeaf()) {
            $message = "Only one leaf per node is allowed";
            throw new RouterException($message);
        }
    
        $parent->children[] = $this;
    }
    
    /**
     * Helper method. Tries to load value with key $key first from $params
     * then from $defaults. 
     * @param array $params
     * @param array $defaults
     * @param string $key
     * @throws RouterException if there is no key in either $params or $defaults
     */
    protected function getValue(&$params, &$defaults, $key) {
        if (isset($params[$key])) {
            return $params[$key];
        }
        else if (isset($defaults[$key])) {
            return $defaults[$key];
        }
        else {
            throw new RouterException('Parameter: '.$key.' is not specified');
        }
    }
} 
