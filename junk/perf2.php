<?php
/** 
 * Turns out the answer doesn't lie here either. This one is slower than the
 * normal parser.
 */
namespace {
goto classdefs;
top:
    
    require "/home/bl/web/bm/big/config.php";
    require __DIR__.'/../vendor/autoload.php';

    $iter = 10;

    $p = new Nope\FastParser;
    $t = microtime(true);
    $cnt = 0;
    for ($i = 0; $i < $iter; $i++) {
        $cnt += ($p->parseClass(\Big\CRM\Data\IOVersion::class) == true);
    }
    var_dump((microtime(true) - $t) / $iter * 1000);
    exit;

    $p = new Nope\Parser;
    $t = microtime(true);
    $cnt = 0;
    for ($i = 0; $i < $iter; $i++) {
        $cnt += ($p->parseClass(\Big\CRM\Data\IOVersion::class) == true);
    }
    var_dump((microtime(true) - $t) / $iter * 1000);
    
    exit;
}

namespace Nope
{
classdefs:

    class FastParser
    {
        const S_NONE = 0;
        const S_NAME = 1;
        const S_JSON = 2;

        public function parseClass($class)
        {
            if (!$class instanceof \ReflectionClass) {
                $class = new \ReflectionClass($class);
            }

            $info = new \stdClass;
            $info->notes = null;
            
            $docBlocks = '';
            $index = [];

            if ($doc = $class->getDocComment()) {
                if ($docBlocks) {
                    $docBlocks .= "\n\0\n";
                }
                $docBlocks .= $doc;
                $index[] = ['class', null];
            }

            foreach ($class->getProperties() as $r) {
                $doc = $r->getDocComment();
                if ($doc) {
                    if ($docBlocks) {
                        $docBlocks .= "\n\0\n";
                    }
                    $name = $r->name;
                    $docBlocks .= $doc;
                    $index[] = ['property', $name];
                }
            }

            foreach ($class->getMethods() as $r) {
                $doc = $r->getDocComment();
                if ($doc) {
                    if ($docBlocks) {
                        $docBlocks .= "\n\0\n";
                    }
                    $name = $r->name;
                    $docBlocks .= $doc;
                    $index[] = ['method', $name];
                }
            } 

            $blocks = explode("\0", $this->stripDocComment($docBlocks));

            $info = [];
            foreach ($blocks as $blockIdx=>$block) {
                list ($type, $name) = $index[$blockIdx];
                $parsed = $this->parse($block);
                if ($name) {
                    $info[$type][$name] = $parsed;
                } else {
                    $info[$type] = $parsed;
                }
            }

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
                        throw new Exception("Failed parsing reflector {$r->name}: ".$ex->getMessage(), null, $ex);
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
                    throw new Exception("JSON parsing failed for $key: ".json_last_error_msg());
                }
                $out[$key] = $cur;
            }
            return $out;
        }

        public function stripDocComment($docComment)
        {
            // docblock start
            $docComment = preg_replace('~ / \*+ \s* ~x', '', $docComment);

            // docblock end
            $docComment = preg_replace('~ \s* \*+ / ( \s* ($|\0) ) ~x', '$1', $docComment);

            // docblock margin
            $docComment = preg_replace('~ (^|\0) \h* \*+ (?!/) \h? ~mx', '$1', $docComment);

            return $docComment;
        }
    }

    goto top;
}

