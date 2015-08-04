<?php
namespace Nope\Test;

require __DIR__.'/../vendor/autoload.php';

class ClassBuilder
{
    private static $classes = [];

    public static function i($reset=false)
    {
        static $i;
        if ($reset || $i===null) {
            $i = new static;
        }
        return $i;
    }

    public function register($classes)
    {
        $classes = (array)$classes;

        $hash = '';
        foreach ($classes as $k=>$v) {
            $hash .= "$k|$v|";
        }
        $classHash = hash('sha256', $hash);
        if (isset(self::$classes[$classHash])) {
            list ($ns, $classes, $classMap) = self::$classes[$classHash];
        }
        else {
            $ns = "__Test_".$classHash;
            $script = "namespace $ns;";
            foreach ($classes as $k=>$v) {
                $script .= $v;
            }

            $script = strtr($script, ['{{ns}}'=>addslashes($ns.'\\')]);

            $classes = get_declared_classes();
            eval($script);
            $classes = array_values(array_diff(get_declared_classes(), $classes));

            $classMap = [];
            $nsLen = strlen($ns);
            foreach ($classes as $class) {
                if (strpos($class, $ns) === 0) {
                    $classMap[substr($class, $nsLen+1)] = $class;
                }
            }

            self::$classes[$classHash] = [$ns, $classes, $classMap];
        }
        return [$ns, $classes, $classMap];
    }

    public function registerOne($class)
    {
        list ($ns, $classes, $classMap) = $this->register($class);
        if (($cnt = count($classes)) != 1) {
            throw new \UnexpectedValueException("Expected one class, found $cnt. Warning! Any classes have been registered in spite of this exception!");
        }
        return current($classes);
    }
}

/**
 * :c1 = {"cv1": true};
 * :c2 = {"cv2": true};
 */
class ParserTestClass
{
    /**
     * :p1 = {"pv1": true};
     * :p2 = {"pv2": true};
     */
    public $property1;
    
    /**
     * :p3 = {"pv3": true};
     * :p4 = {"pv4": true};
     */
    public $property2;

    /**
     * :m1 = {"mv1": true};
     * :m2 = {"mv2": true};
     */
    public function method1() {}

    /**
     * :m3 = {"mv3": true};
     * :m4 = {"mv4": true};
     */
    public function method2() {}
}

/**
 * :c2 = {"cv2": true};
 * :c3 = {"cv3": true};
 */
class ParserTestChildClass extends ParserTestClass
{
    /**
     * :p5 = {"pv5": true};
     * :p6 = {"pv6": true};
     */
    public $property2;

    /**
     * :p7 = {"pv7": true};
     * :p8 = {"pv8": true};
     */
    protected $property3;

    /**
     * :m5 = {"mv5": true};
     * :m6 = {"mv6": true};
     */
    public function method2() {}

    /**
     * :m7 = {"mv7": true};
     * :m8 = {"mv8": true};
     */
    protected function method3() {}
}

/**
 * :c1 = {
 */
class ParserTestBadClassNoteClass
{}

class ParserTestBadPropertyNoteClass
{
    /**
     * :p1 = {
     */
    public $foo;
}

class ParserTestBadMethodNoteClass
{
    /**
     * :m1 = {
     */
    public function foo() {}
}
