<?php
namespace Nope;

class Helper
{
    private function __construct()
    {}

    // ReflectionClass->getMethods($filter) doesn't work with PHP_INT_MAX & ~ReflectionMethod::IS_STATIC
    public static function methodFilterAll()
    {
        static $methodFilterAll = null;
        if ($methodFilterAll === null) {
            $rc = new \ReflectionClass('ReflectionMethod');
            
            $methodFilterAll = 0;
            foreach ($rc->getConstants() as $k=>$v) {
                if (isset($k[2]) && $k[0] == 'I' && $k[1] == 'S' && $k[2] == '_') {
                    $methodFilterAll |= $v;
                }
            }
        }
        return $methodFilterAll;
    }

    // ReflectionClass->getProperties($filter) doesn't work with PHP_INT_MAX & ~ReflectionProperty::IS_STATIC
    public static function propertyFilterAll()
    {
        static $propertyFilterAll = null;
        if ($propertyFilterAll === null) {
            $rc = new \ReflectionClass('ReflectionProperty');
            
            $propertyFilterAll = 0;
            foreach ($rc->getConstants() as $k=>$v) {
                if (isset($k[2]) && $k[0] == 'I' && $k[1] == 'S' && $k[2] == '_') {
                    $propertyFilterAll |= $v;
                }
            }
        }
        return $propertyFilterAll;
    }
}
