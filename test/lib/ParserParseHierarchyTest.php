<?php
namespace Nope\Test;

class ParserParseHierarchyTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->parser = new \Nope\Parser;
    }

    public function testParseHierarchyNoteMerge()
    {
        list ($ns, $classes, $map) = ClassBuilder::i()->register('
            /**
             * :foo = 1;
             * :bar = 2;
             */
            class Foo {}

            /**
             * :foo = 2;
             * :baz = 3;
             */
            class Bar extends Foo {}
        ');

        $result = $this->parser->parseHierarchy($map['Bar']);
        $this->assertEquals(
            ['foo' => 2, 'bar' => 2, 'baz' => 3],
            $result->notes);

        $this->assertEquals(
            ['foo' => 2, 'baz' => 3],
            $result->hierarchy[0]->notes);

        $this->assertEquals(
            ['foo' => 1, 'bar' => 2],
            $result->hierarchy[1]->notes);
    }

    public function testHierarchyNoteMergeNull()
    {
        list ($ns, $classes, $map) = ClassBuilder::i()->register('
            /** :foo = 1; */
            class Foo {}

            /** :foo = null; */
            class Bar extends Foo {}
        ');

        $hierarchy = $this->parser->parseHierarchy($map['Bar']);
        $expected = ['foo' => null];
        $this->assertEquals($expected, $hierarchy->notes);
    }

    public function testHierarchyPropertyMerge()
    {
        list ($ns, $classes, $map) = ClassBuilder::i()->register('
            class Foo {
                /** :big = true; */
                public $foo;
            }
            class Bar extends Foo {
                /** :whoop = true; */
                public $foo;
            }
        ');

        $hierarchy = $this->parser->parseHierarchy($map['Bar']);
        $expected = ['big' => true, 'whoop' => true];
        $this->assertEquals($expected, $hierarchy->properties['foo']);
    }

    public function testHierarchyMethodMerge()
    {
        list ($ns, $classes, $map) = ClassBuilder::i()->register('
            class Foo {
                /** :big = true; */
                public function pants() {}
            }
            class Bar extends Foo {
                /** :whoop = true; */
                public function pants() {}
            }
        ');

        $hierarchy = $this->parser->parseHierarchy($map['Bar']);
        $expected = ['big' => true, 'whoop' => true];
        $this->assertEquals($expected, $hierarchy->methods['pants']);
    }
}
