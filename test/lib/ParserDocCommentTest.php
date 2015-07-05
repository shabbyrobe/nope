<?php
namespace Nope\Test;

class ParserDocCommentTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->parser = new \Nope\Parser;
    }

    /** @covers Nope\Parser::parseDocComment */
    public function testParseDocComment()
    {
        $in = "/**\n * :pants = true;\n */";
        $this->assertEquals(['pants'=>true], $this->parser->parseDocComment($in));
    }

    /** @covers Nope\Parser::stripDocComment */
    public function testStripDocCommentMultiline()
    {
        $parsed = $this->parser->stripDocComment(implode("\n", [
            "/**",
            " * foo",
            "   *     bar",
            "baz",
            "*/",
        ]));

        // only the first whitespace after the * is stripped:
        $expected = "foo\n    bar\nbaz";
        $this->assertEquals($expected, $parsed);
    }

    /** @covers Nope\Parser::stripDocComment */
    public function testStripDocCommentFromNonDocblock()
    {
        // /* */ instead of /** */
        $parsed = $this->parser->stripDocComment(
            "/*\n * foo\n*/"
        );
        $this->assertEquals("foo", $parsed);
    }
    
    /** @covers Nope\Parser::stripDocComment */
    public function testStripDocCommentWorksWhenInputIsNotComment()
    {
        $data = "foo\nbar\nbaz";
        $parsed = $this->parser->stripDocComment($data);
        $this->assertEquals($data, $parsed);
    }

    /**
     * @covers Nope\Parser::stripDocComment
     */
    public function testStripReallyUglyComment()
    {
        $data = implode("\n", [
            "/************ foo",
            "*   bar",
            "    *** baz",
            "***/",
        ]);
        $expected = implode("\n", [
            "foo",
            "  bar",
            "** baz",
        ]);
        $parsed = $this->parser->stripDocComment($data);
        $this->assertEquals($expected, $parsed);
    }

    /** @covers Nope\Parser::stripDocComment */
    public function testStripInlineWithNoSeparatingWhitespace()
    {
        $in = "/**foo**/";
        $expected = "foo";
        $parsed = $this->parser->stripDocComment($in);
        $this->assertEquals($expected, $parsed);
    }

    /** @covers Nope\Parser::stripDocComment */
    public function testStripInlineWithNoSeparatingWhitespaceBeforeEnd()
    {
        $in = "/**\nfoo**/";
        $expected = "foo";
        $parsed = $this->parser->stripDocComment($in);
        $this->assertEquals($expected, $parsed);
    }
}
