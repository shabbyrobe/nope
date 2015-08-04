<?php
require __DIR__.'/../vendor/autoload.php';

/**
 * :foo = {};
 * :bar = true;
 */
class Foo
{
    /** :yep = false; */
    public $pants;

    /** :yep = true; */
    public function setWhoopee()
    {} 
}

/**
 * :foo = ["z"];
 * :baz = "DING";
 */
class Bar extends Foo
{
    /** :yep = true; */
    public $pants;

    public function setWhoopee()
    {}
}

$p = new \Nope\Parser;
dump($p->parseHierarchy(Bar::class));

