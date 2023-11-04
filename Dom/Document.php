<?php

/**
 * @Package:    PHPFuse - DOM Main class
 * @Author:     Daniel Ronkainen
 * @Licence:    The MIT License (MIT), Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace PHPFuse\Output\Dom;

class Document
{
    public const TAG_NO_ENDING = [
        "meta", "link", "img", "br", "hr", "input", "keygen", "param", "source", "track", "embed"
    ];

    protected $elements;
    private $html;
    private $el;
    private static $inst;

    /**
     * Init DOM instance
     * @param  string $key DOM access key
     * @return new self
     */
    public static function dom(string $key)
    {
        if (empty(self::$inst[$key])) {
            self::$inst[$key] = new self();
        }
        return self::$inst[$key];
    }

    /**
     * Create and bind tag to a key so it can be overwritten
     * @param  string       $tag     HTML tag (without brackets)
     * @param  string       $key     Bind tag to key
     * @param  bool|boolean $prepend Prepend instead of append
     * @return self
     */
    public function bindTag(string $tag, string $key, bool $prepend = false)
    {
        if ($prepend) {
            $this->el = $this->createPrepend($tag, null, $key);
        } else {
            $this->el = $this->create($tag, null, $key);
        }
        return $this->el;
    }



    /**
     * Create (append) element
     * @param  string $element HTML tag (without brackets)
     * @param  string $value   add value to tag
     * @return self
     */
    public function create($element, $value = null, ?string $bind = null)
    {
        $inst = new Element($element, $value);

        if (!is_null($bind)) {
            $this->elements[$bind] = $inst;
        } else {
            $this->elements[] = $inst;
        }

        return $inst;
    }

    /**
     * Prepend element first
     * @param  string $element HTML tag (without brackets)
     * @param  string $value   add value to tag
     * @return self
     */
    public function createPrepend(string $element, ?string $value = null, ?string $bind = null)
    {
        $inst = new Element($element, $value);
        if (is_null($this->elements)) {
            $this->elements = array();
        }

        if (!is_null($bind)) {
            $new[$bind] = $inst;
            $this->elements = array_merge($new, $this->elements);
        } else {
            $this->elements = array_merge([$inst], $this->elements);
        }

        return $inst;
    }

    /**
     * Get one element from key
     * @return Response\Dom\Element
     */
    public function getElement($k)
    {
        return ($this->elements[$k] ?? null);
    }

    /**
     * Get all elements
     * @return array
     */
    public function getElements()
    {
        return $this->elements;
    }

    public function getTag(string $key)
    {
        return ($this->el[$key] ?? null);
    }

    /**
     * Execute and get Dom/document
     * @param  callable|null $call Can be used to manipulate element within feed
     * @return string
     */
    public function execute(?callable $call = null)
    {
        $this->html = "";
        if (is_null($this->elements) && ($inst = $this->withElement())) {
            $this->elements[] = $inst;
        }
        if (is_array($this->elements)) {
            $this->build($this->elements, $call);
        }
        return $this->html;
    }

    /**
     * Get get Dom/document (Will only trigger execute once per instance)
     * @return string
     */
    public function get()
    {
        if (is_null($this->html)) {
            $this->execute();
        }
        return $this->html;
    }


    public function __toString()
    {
        return $this->get();
    }


    /**
     * Build document
     * @param  array         $arr  elements
     * @param  callable|null $call Can be used to manipulate element within feed
     */
    private function build(array $arr, ?callable $call = null)
    {
        foreach ($arr as $k => $a) {
            $hasNoEnding = in_array($a->getEl(), $this::TAG_NO_ENDING);
            if (!is_null($call)) {
                $call($a, $k, $hasNoEnding);
            }

            if (!$a->hideTagValid()) {
                $this->html .= "\t<".$a->getEl().$a->buildAttr().">";
            }
            if (!$hasNoEnding) {
                $this->html .= $a->getValue();
            }
            if (isset($a->elements)) {
                $this->build($a->elements, $call);
            }
            if (!$hasNoEnding && !$a->hideTagValid()) {
                $this->html .= "</".$a->getEl().">\n";
            }
            if ($hasNoEnding && !$a->hideTagValid()) {
                $this->html .= "\n";
            }
        }
    }
}
