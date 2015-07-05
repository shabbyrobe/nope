<?php
namespace Nope;

use Seld\JsonLint\JsonParser;

class Parser
{
    const S_NONE = 0;
    const S_NAME = 1;
    const S_JSON = 2;

    public function parseClass($class, $propertyFilter=null, $methodFilter=null)
    {
        if (!$class instanceof \ReflectionClass) {
            $class = new \ReflectionClass($class);
        }

        $info = new \stdClass;
        $info->notes = null;
        
        $doc = $class->getDocComment();
        if ($doc) {
            try {
                $info->notes = $this->parse($this->stripDocComment($doc));
            }
            catch (Exception $ex) {
                throw new Exception("Failed parsing class docblock '{$class->name}'. ".$ex->getMessage(), null, $ex);
            }
        }

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
        
        return $info;
    }

    public function parseReflectors($reflectors)
    {
        $notes = array();
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
