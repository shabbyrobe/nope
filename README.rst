Nope! - JSON Docblock Annotation Parser for PHP
===============================================

Specify annotations in docblocs as JSON:

.. code-block:: php

    <?php
    /**
     * Hellos your hellos
     *
     * :c1 = {
     *     "foo": true,
     *     "bar": ["baz", "qux"],
     *     "ding": {"dong": "woohoo"}
     * };
     * :c2 = {"foo": false};
     *
     * @author Mr. Pants
     */
    class Hello
    {
        /** :p1 = {"pants": true}; */
        public $pants;

        /** :m1 = {"foo": true}; */
        function world($foo, $bar)
        {
            return "Hello, World!";
        }
    }

    $parser = new Nope\Parser();
    $out = $parser->parseClass('Hello');


Result::

    stdClass#1 (
        [notes] => array (
            'c1' => array (
                'foo' => true
                'bar' => array (
                    '0' => 'baz'
                    '1' => 'qux'
                )
                'ding' => array (
                    'dong' => 'woohoo'
                )
            )
            'c2' => array (
                'foo' => false
            )
        )
        [methods] => array (
            'world' => array(
                'm1' => array (
                    'foo' => true
                )
            )
        )
        [properties] => array (
            'pants' => array (
                'p1' => array (
                    'pants' => true
                )
            )
        )
    )


*Nope* will look for any line inside a docblock that starts with a ``:`` character and
begin parsing. Everything from the colon to the ``=`` sign (excluding trailing whitespace)
is taken as the namespace. Anything after the ``=`` sign must be a valid JSON object.
Parsing ends when a ``;`` is encountered as the last character on a line.

If you need a line in your docblock to start with ``:`` and **not** be parsed by *Nope*,
escape it with a backslash: ``\:``.

Annotations cannot exist on the same line. The ``:`` character must be at the start of a
line (excluding docblock margins) and the ``;`` character must be at the end. Due to the
fortunate property that JSON strings cannot span multiple lines, it is not possible for
the string ``;\n`` to appear anywhere inside valid JSON. This is the property we exploit
to make *Nope* possible. So the following example cannot be parsed successfully by *Nope*:

.. code-block:: php
    
    <?php
    /** :foo = true; :bar = true; */
    function impossible() {}


Please, please, please be careful when adding new namespaces to your libraries and
applications. Ideally, you should define one namespace for your entire application and
embed your annotations as an object inside that. This solves the "one annotation per line"
problem as well:

.. code-block:: php

    <?php
    /** :myapp = {"foo": "bar", "baz": "qux"}; */
    function good() {}

    /**
     * :foo = "bar";
     * :baz = "qux";
     */
    function pleasedont() {}


API
---

Parse all annotations from a class, trait, or interface:

.. code-block:: php

    <?php
    /** :foo = {"yep": true}; */
    class Pants
    {
        /** :foo = {"yep": true}; */
        public $property;

        /** :foo = {"yep": true}; */
        public function test() {}
    }

    $result = $parser->parseClass('Pants');
    $result = $parser->parseClass(new \ReflectionClass('Pants'));


This results in::

    stdClass#1 (
        [notes] => array (
            'foo' => array (
                'yep' => true
            )
        )
        [properties] => array (
            'property' => array (
                'foo' => array (
                    'yep' => true
                )
            )
        )
        [methods] => array (
            'test' => array (
                'foo' => array (
                    'yep' => true
                )
            )
        )
    )

Parse all annotations from a doc comment:

.. code-block:: php

    <?php
    /** :foo = {"bar": true}; */
    function func()
    {}

    $function = new ReflectionFunction('func');
    $notes = $parser->parseDocComment($function->getDocComment());
    $parsesTo = array(
        'foo'=>['bar'=>true],
    );


Parse all annotations from a string:

.. code-block:: php

    <?php
    $string = ':foo = {"bar": true};';
    $notes = $parser->parse($string);
    $parsesTo = array(
        'foo'=>["bar"=>true],
    );


Parse all annotations from an array of Reflectors (must support the ``name`` property and
the ``getDocComment()`` method):

.. code-block:: php

    <?php
    $rc = new ReflectionClass('Pants');
    $notes = $parser->parseReflectors($rc->getMethods(ReflectionMethod::IS_STATIC));


Isn't this a solved problem?
----------------------------

Nope!

I've had about half a dozen goes at this one over the years, and I'm not satisfied with
the available solutions. I like attribute-based metaprogramming and think it should be
supported natively, but it doesn't look like that's coming to PHP any time soon.

There are already indeed heaps of tools for this already, several of which I have
unleashed on the world myself (I'm sorry).

A common approach is to define a complex new language. These languages are often slightly
different from vanilla PHP, which imposes a cognitive load each time you have to switch in
and out of using them. You also tend to write annotations far less frequently than
you write other code, so there is much time spent looking at manuals to fill in the blanks. 
They also require complex PHP-based implementations of slow parsers to even be read in the
first place. I have remained uncomfortable with these kinds of solutions for a long time -
they are far too slow and have way too many moving parts.

I've even had two failed attempts at a leaner alternative to this in my Data Mapper
project `Amiss <http://github.com/shabbyrobe/amiss>`_ (see v3 and v4), both of which fell
down because they were too unfamiliar and/or inflexible.

I've remained convinced that there was a native C-based solution to this lurking in PHP's
standard library for a long time, and I'm stunned that it took me this long to realise
``json_decode`` was staring me in the face this whole time.

JSON is a good fit for this job. It's unambiguous, ubiquitous and there is a fast C-based
parser available to PHP in a single function call. *Nope* takes advantage of these
properties by finding a way to unambiguously embed JSON into the unstructured text strings
you find in doc comments.

