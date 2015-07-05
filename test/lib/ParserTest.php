<?php
namespace Nope\Test;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->parser = new \Nope\Parser;
    }
    
    /**
     * @covers Nope\Parser::parse
     */
    public function testParseSingleValuelessNote()
    {
        $parsed = $this->parser->parse(':note = {};');
        $this->assertEquals(array('note'=>[]), $parsed);
    }
 
    /**
     * @covers Nope\Parser::parse
     */
    public function testParseSingleValueNote()
    {
        $parsed = $this->parser->parse(':note = {"foo": "bar"};');
        $this->assertEquals(array('note'=>["foo"=>"bar"]), $parsed);
    }

    /**
     * @covers Nope\Parser::parse
     */
    public function testParseManyNotesMultiline()
    {
        $parsed = $this->parser->parse(
            ":one = {};\n".
            ":two = {};\n"
        );
        $this->assertEquals(array("one"=>[], "two"=>[]), $parsed);
    }

    /**
     * @covers Nope\Parser::parse
     */
    public function testParseComplexMultilineNote()
    {
        $parsed = $this->parser->parse('
            :one = {
                "is": {"pants": true},
                "so": [1, 2, 3]
            }
            ;
        ');
        $this->assertEquals(['one'=>['is'=>['pants'=>true], 'so'=>[1, 2, 3]]], $parsed);
    }

    public function testParseIgnoresEscapedKey()
    {
        $in = "\:foo = {}";
        $parsed = $this->parser->parse($in);
        $this->assertEquals([], $parsed);
    }

    public function testParseIgnoresKeyInsideLine()
    {
        $in = "This is how you do it: :foo = {}";
        $parsed = $this->parser->parse($in);
        $this->assertEquals([], $parsed);
    }

    public function testParseAllowsHwspBeforeKey()
    {
        $in = "      \t\t\t    :foo = {};";
        $parsed = $this->parser->parse($in);
        $this->assertEquals(['foo'=>[]], $parsed);
    }

    public function testParseDoubleNameFails()
    {
        $in = ":foo = :bar = {};";
        $this->setExpectedException(
            "Nope\Exception",
            "JSON parsing failed for 'foo' - Parse error on line 1:\n".
            ":bar = {}\n".
            "^"
        );
        $parsed = $this->parser->parse($in);
    }

    public function testParseIgnoresSemicolonInsideJSON()
    {
        $in = ':foo = {"bar": "baz;qux"};';
        $parsed = $this->parser->parse($in);
        $this->assertEquals(['foo'=>['bar'=>'baz;qux']], $parsed);
    }

    public function testParseIgnoresEqualsInsideJSON()
    {
        $in = ':foo = {"bar": "baz=qux"};';
        $parsed = $this->parser->parse($in);
        $this->assertEquals(['foo'=>['bar'=>'baz=qux']], $parsed);
    }

    /**
     * @dataProvider dataEmptyName
     */
    public function testParseEmptyNameFails($in)
    {
        $this->setExpectedException(
            "Nope\Exception",
            "Unexpected token '=', expected annotation name"
        );
        $parsed = $this->parser->parse($in);
    }

    function dataEmptyName()
    {
        return [
            [': = {};'],
            [':= {};'],
        ];
    }

    public function testParseEndsExpectingEqualsFailure()
    {
        $in = ":foo";
        $this->setExpectedException(
            "Nope\Exception",
            "Unexpected end of definition for key 'foo'"
        );
        $parsed = $this->parser->parse($in);
    }

    public function testParseEndsExpectingJson()
    {
        $in = ':foo = {"baz';
        $this->setExpectedException(
            "Nope\Exception",
            "Unexpected end of JSON for key 'foo'"
        );
        $parsed = $this->parser->parse($in);
    }

    public function testParseJSONStrings()
    {
        $in = ':foo = "hello";'."\n".
              ':bar = "world";';
        $parsed = $this->parser->parse($in);
        $this->assertEquals(["foo"=>"hello", "bar"=>"world"], $parsed);
    }

    public function testParseJSONBools()
    {
        $in = ':foo = true;'."\n".
              ':bar = false;';
        $parsed = $this->parser->parse($in);
        $this->assertEquals(["foo"=>true, "bar"=>false], $parsed);
    }

    public function testParseJSONNulls()
    {
        $in = ':foo = null;'."\n".
              ':bar = null;';
        $parsed = $this->parser->parse($in);
        $this->assertEquals(["foo"=>null, "bar"=>null], $parsed);
    }

    public function testParseJSONArrays()
    {
        $in = ':foo = [1, 2];'."\n".
              ':bar = [3, 4];';
        $parsed = $this->parser->parse($in);
        $this->assertEquals(["foo"=>[1, 2], "bar"=>[3, 4]], $parsed);
    }

    public function testParseJSONNumbers()
    {
        $in = ':foo = 123;'."\n".
              ':bar = 45.6;';
        $parsed = $this->parser->parse($in);
        $this->assertEquals(["foo"=>123, "bar"=>45.6], $parsed);
    }
}
