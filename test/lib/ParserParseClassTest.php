<?php
namespace Nope\Test;

class ParserParseClassTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->parser = new \Nope\Parser;
    }
    
    /**
     * @covers Nope\Parser::parseClass
     */
    public function testParseClassUsingStringName()
    {
        $info = $this->parser->parseClass(__NAMESPACE__.'\ParserTestClass');
        $test = $this->parser->parseClass(new \ReflectionClass(__NAMESPACE__.'\ParserTestClass'));
        $this->assertEquals($info, $test);
    }

    /**
     * @covers Nope\Parser::parseClass
     * @covers Nope\Parser::parseReflectors
     */
    public function testParseClassFull()
    {
        $info = $this->parser->parseClass(new \ReflectionClass(__NAMESPACE__.'\ParserTestClass'));
        $expected = (object)array(
            'notes'=>array('c1'=>['cv1'=>true], 'c2'=>['cv2'=>true]),
            'methods'=>array(
                'method1'=>array('m1'=>['mv1'=>true], 'm2'=>['mv2'=>true]),
                'method2'=>array('m3'=>['mv3'=>true], 'm4'=>['mv4'=>true]),
            ),
            'properties'=>array(
                'property1'=>array('p1'=>['pv1'=>true], 'p2'=>['pv2'=>true]),
                'property2'=>array('p3'=>['pv3'=>true], 'p4'=>['pv4'=>true]),
            ),
        );

        $this->assertEquals($expected, $info);
    }
    
    public function testParseInheritance()
    {
        $info = $this->parser->parseClass(new \ReflectionClass(__NAMESPACE__.'\ParserTestChildClass'));
        $expected = (object)array(
            'notes'=>array('c2'=>['cv2'=>true], 'c3'=>['cv3'=>true]),
            'methods'=>array(
                'method1'=>array('m1'=>['mv1'=>true], 'm2'=>['mv2'=>true]),
                'method2'=>array('m5'=>['mv5'=>true], 'm6'=>['mv6'=>true]),
                'method3'=>array('m7'=>['mv7'=>true], 'm8'=>['mv8'=>true]),
            ),
            'properties'=>array(
                'property1'=>array('p1'=>['pv1'=>true], 'p2'=>['pv2'=>true]),
                'property2'=>array('p5'=>['pv5'=>true], 'p6'=>['pv6'=>true]),
                'property3'=>array('p7'=>['pv7'=>true], 'p8'=>['pv8'=>true]),
            ),
        );

        $this->assertEquals($expected, $info);
    }

    public function testParseFilters()
    {
        $info = $this->parser->parseClass(
            new \ReflectionClass(__NAMESPACE__.'\ParserTestChildClass'),
            \ReflectionMethod::IS_PUBLIC,
            \ReflectionProperty::IS_PUBLIC
        );

        $expected = (object)array(
            'notes'=>array('c2'=>['cv2'=>true], 'c3'=>['cv3'=>true]),
            'methods'=>array(
                'method1'=>array('m1'=>['mv1'=>true], 'm2'=>['mv2'=>true]),
                'method2'=>array('m5'=>['mv5'=>true], 'm6'=>['mv6'=>true]),
            ),
            'properties'=>array(
                'property1'=>array('p1'=>['pv1'=>true], 'p2'=>['pv2'=>true]),
                'property2'=>array('p5'=>['pv5'=>true], 'p6'=>['pv6'=>true]),
            ),
        );

        $this->assertEquals($expected, $info);
    }

    public function testParseClassFails()
    {
        $name = __NAMESPACE__.'\ParserTestBadClassNoteClass';
        $this->setExpectedException(
            'Nope\Exception', 
            "Failed parsing class docblock '$name'. Unexpected end of JSON for key 'c1'"
        );
        $test = $this->parser->parseClass(new \ReflectionClass($name));
    }

    public function testParseClassPropertyFails()
    {
        $name = __NAMESPACE__.'\ParserTestBadPropertyNoteClass';
        $this->setExpectedException(
            'Nope\Exception', 
            "Failed parsing reflector 'foo'. Unexpected end of JSON for key 'p1'"
        );
        $test = $this->parser->parseClass(new \ReflectionClass($name));
    }

    public function testParseClassMethodFails()
    {
        $name = __NAMESPACE__.'\ParserTestBadMethodNoteClass';
        $this->setExpectedException(
            'Nope\Exception', 
            "Failed parsing reflector 'foo'. Unexpected end of JSON for key 'm1'"
        );
        $test = $this->parser->parseClass(new \ReflectionClass($name));
    }
}
