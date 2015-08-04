<?php
namespace Nope;

use Seld\JsonLint\JsonParser;

class Parser
{
    const S_NONE = 0;
    const S_NAME = 1;
    const S_JSON = 2;

    private $internalCache = [];

    public function parseHierarchy($class, $propertyFilter=null, $methodFilter=null)
    {
        if (!$class instanceof \ReflectionClass) {
            $class = new \ReflectionClass($class);
        }

        $key = $class->name.'|'.$propertyFilter.'|'.$methodFilter;
        if (isset($this->internalCache['hierarchy'][$key])) {
            return $this->internalCache['hierarchy'][$key];
        }

        $info = (object)[
            'hierarchy'  => [],
            'notes'      => [],
            'methods'    => [],
            'properties' => []
        ];

        // these are so that if 'null' is actually set as the note's value, we can still
        // detect that we already set it just with isset rather than array_key_exists
        $setMethods = [];
        $setProperties = [];
        $setNotes = [];

        $index = 0;
        $currentClass = $class;
        while ($currentClass) {
            $info->hierarchy[$index] = $current = $this->parseClass($currentClass, $propertyFilter, $methodFilter);

            foreach ($current->notes as $k=>$v) {
                if (!isset($setNotes[$k])) {
                    $info->notes[$k] = $v;
                    $setNotes[$k] = true;
                }
            }

            foreach ($current->methods as $name=>$notes) {
                foreach ($notes as $k=>$v) {
                    if (!isset($setMethods[$name][$k])) {
                        $info->methods[$name][$k] = $v;
                        $setMethods[$name][$k] = true;
                    }
                }
            }

            foreach ($current->properties as $name=>$notes) {
                foreach ($notes as $k=>$v) {
                    if (!isset($setProperties[$name][$k])) {
                        $info->properties[$name][$k] = $v;
                        $setProperties[$name][$k] = true;
                    }
                }
            }

            $currentClass = $currentClass->getParentClass();
            ++$index;
        }

        return $this->internalCache['hierarchy'][$key] = $info;
    }

    public function parseClass($class, $propertyFilter=null, $methodFilter=null)
    {
        if (!$class instanceof \ReflectionClass) {
            $class = new \ReflectionClass($class);
        }

        $key = $class->name.'|'.$propertyFilter.'|'.$methodFilter;
        if (isset($this->internalCache['class'][$key])) {
            return $this->internalCache['class'][$key];
        }

        $info = new \stdClass;
        $info->notes = $this->parseClassDocBlock($class);

        $info->methods = $this->parseReflectors(
            $methodFilter === null
                ? $class->getMethods()
                : $class->getMethods($methodFilter)
        );

        $info->properties = $this->parseReflectors(
            $propertyFilter === null
                ? $class->getProperties()
                : $class->getProperties($propertyFilter)
        );
        
        return $this->internalCache['class'][$key] = $info;
    }

    public function parseClassDocBlock($class)
    {
        if (!$class instanceof \ReflectionClass) {
            $class = new \ReflectionClass($class);
        }

        $doc = $class->getDocComment();
        if ($doc) {
            try {
                return $this->parse($this->stripDocComment($doc));
            }
            catch (Exception $ex) {
                throw new Exception("Failed parsing class docblock '{$class->name}'. ".$ex->getMessage(), null, $ex);
            }
        }
        return [];
    }

    public function parseReflectors($reflectors)
    {
        $notes = [];
        foreach ($reflectors as $r) {
            $comment = $r->getDocComment();
            $name = $r->name;
            if ($comment) {
                try {
                    $curNotes = $this->parse($this->stripDocComment($comment));
                    if ($curNotes) {
                        $notes[$name] = $curNotes;
                    }
                }
                catch (Exception $ex) {
                    throw new Exception("Failed parsing reflector '{$r->name}'. ".$ex->getMessage(), null, $ex);
                }
            }
        }
        return $notes;
    }

    public function parseDocComment($docComment)
    {
        return $this->parse($this->stripDocComment($docComment));
    }
    
    public function parse($string)
    {
        $tokens = preg_split(
            '~ ( 
                  ^ \h* :    # start - ":key" must be the first non-hwsp thing on the line
                | =              
                | ; \h* $    # end - ";" must be the last non-hwsp thing on the line
            ) ~xm', 
            $string, null, 
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
        );

        $state = self::S_NONE;
        $curName = null;
        $jsonBuf = null;
        $jsonDepth = 0;

        $parsed = [];

        foreach ($tokens as $tok) {
            if ($state == self::S_NONE) {
                if (ltrim($tok) == ':') {
                    $state = self::S_NAME;
                    $curName = null;
                }
            }
            elseif ($state == self::S_NAME) {
                if ($tok == '=') {
                    if (!$curName) {
                        throw new Exception("Unexpected token '=', expected annotation name");
                    }
                    $state = self::S_JSON;
                }
                else {
                    $curName = trim($tok);
                }
            }
            elseif ($state == self::S_JSON) {
                if ($tok[0] == ';') {
                    $parsed[$curName] = $jsonBuf;
                    $curName = null;
                    $jsonBuf = null;
                    $state = self::S_NONE;
                }
                else {
                    $jsonBuf .= $tok;
                }
            }
        }

        if ($state == self::S_JSON) {
            throw new Exception("Unexpected end of JSON for key '$curName'");
        }
        elseif ($state != self::S_NONE) {
            throw new Exception("Unexpected end of definition for key '$curName'");
        }

        $out = [];
        foreach ($parsed as $key=>$json) {
            $cur = json_decode($json, !!'assoc');
            if ($cur === null && ($err = json_last_error())) {
                $parser = new JsonParser();
                $message = $parser->lint(trim($json))->getMessage();
                throw new Exception("JSON parsing failed for '$key' - ".$message);
            }
            $out[$key] = $cur;
        }
        return $out;
    }

    public function stripDocComment($docComment)
    {
        // docblock start
        $docComment = preg_replace('~ \s* / \*+ \s* ~x', '', $docComment);
        
        // docblock end
        $docComment = preg_replace('~ \s* \*+ / \s* $~x', '', $docComment);
        
        // docblock margin
        $docComment = preg_replace('~^ \h* \* \h? ~mx', '', $docComment);

        return $docComment;
    }
}
