<?php
namespace Nope\Test;

require __DIR__.'/../vendor/autoload.php';

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
