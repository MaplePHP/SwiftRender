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
        "meta",
        "link",
        "img",
        "br",
        "hr",
        "input",
        "keygen",
        "param",
        "source",
        "track",
        "embed"
    ];

    protected $elements;
    private $html;
    private $elem;
    private static $inst;

    public function __toString(): string
    {
        return $this->get();
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

    /**
     * Init DOM instance
     * @param  string $key DOM access key
     * @return self
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
            $this->elem = $this->createPrepend($tag, null, $key);
        } else {
            $this->elem = $this->create($tag, null, $key);
        }
        return $this->elem;
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
     * @return Element|null
     */
    public function getElement(string $key): ?Element
    {
        return ($this->elements[$key] ?? null);
    }

    /**
     * Get all elements
     * @return array
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Get html tag
     * @param  string $key
     * @return string|null
     */
    public function getTag(string $key): ?string
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
        if (is_null($this->elements)) {
            if (method_exists($this, "withElement")) {
                $inst = $this->withElement();
                $this->elements[] = $inst;
            }
        }
        if (is_array($this->elements)) {
            $this->build($this->elements, $call);
        }
        return $this->html;
    }
    
    /**
     * Build document
     * @param  array         $arr  elements
     * @param  callable|null $call Can be used to manipulate element within feed
     * @return void
     */
    private function build(array $arr, ?callable $call = null): void
    {
        foreach ($arr as $key => $elemObj) {
            $hasNoEnding = $this->elemHasEnding($elemObj->getEl());
            $this->buildCallable($elemObj, $key, $hasNoEnding, $call);

            if (!$elemObj->hideTagValid()) {
                $this->html .= "\t<" . $elemObj->getEl() . $elemObj->buildAttr() . ">";
            }
            if (!$hasNoEnding) {
                $this->html .= $elemObj->getValue();
            }
            if (isset($elemObj->elements)) {
                $this->build($elemObj->elements, $call);
            }
            if (!$hasNoEnding && !$elemObj->hideTagValid()) {
                $this->html .= "</" . $elemObj->getEl() . ">\n";
            }
            if ($hasNoEnding && !$elemObj->hideTagValid()) {
                $this->html .= "\n";
            }
        }
    }

    private function buildCallable($elemObj, $key, $hasNoEnding, ?callable $call): void
    {
        if (!is_null($call)) {
            $call($elemObj, $key, $hasNoEnding);
        }
    }

    /**
     * Validate if element has ending
     * @param  string $elem
     * @return bool
     */
    final protected function elemHasEnding(string $elem): bool
    {
        return (in_array($elem, $this::TAG_NO_ENDING));
    }
}
