<?php

/**
 * @Package:    PHPFuse - DOM Element class
 * @Author:     Daniel Ronkainen
 * @Licence:    The MIT License (MIT), Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 * @Version:    1.0.0
 */

namespace PHPFuse\Output\Dom;

use BadMethodCallException;

class Element extends Document
{
    private $elem;
    private $attr = array();
    private $snippet;
    private $value;
    private $hideEmptyTag = false;
    //private $node;
    //private $inst;

    public function __construct(string $elem, ?string $value, bool $snippet = false)
    {
        $this->elem = $elem;
        $this->value = $value;
        $this->snippet = $snippet;
    }

    /**
     * Overwrite the current element
     * @param string $elem HTML Tag name
     */
    public function setElement(string $elem): self
    {
        $this->elem = $elem;
        return $this;
    }

    /**
     * Set html attribute
     * @param  string      $key attr key
     * @param  string|null $val attr value
     * @return self
     */
    public function attr(string $key, ?string $val = null)
    {
        $this->attr[$key] = $val;
        return $this;
    }

    /**
     * Set multiple html attributes
     * @param  array    [key => value]
     * @return self
     */
    public function attrArr(?array $arr)
    {
        if (is_array($arr)) {
            $this->attr = array_merge($this->attr, $arr);
        }
        return $this;
    }

    /**
     * Hide html tag if its value is empty
     * @param  bool   $bool
     * @return self
     */
    public function hideEmptyTag(bool $bool): self
    {
        $this->hideEmptyTag = $bool;
        return $this;
    }

    /**
     * Validate hide tag value
     * @return bool
     */
    protected function hideTagValid(): bool
    {
        return (bool)(($this->hideEmptyTag && !$this->value));
    }

    /**
     * Add value to attr
     * @param  string $key
     * @param  string $value
     * @param  string $sep
     * @return self
     */
    public function attrAdd(string $key, string $value, string $sep = " "): self
    {
        if (isset($this->attr[$key])) {
            $this->attr[$key] .= "{$sep}{$value}";
        } else {
            $this->attr[$key] = $value;
        }
        return $this;
    }

    // Same as above
    public function attrAddTo(string $key, string $value, string $sep = " "): self
    {
        return $this->attrAdd($key, $value, $sep);
    }

    /**
     * Set elem value <elem>[VALUE]</elem>
     * @param self
     */
    public function setValue(?string $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Set elem value
     * @param string
     */
    public function getValue(): string
    {
        return (string)$this->value;
    }

    /**
     * Get elem/HTML tag
     * @return string
     */
    public function getEl(): string
    {
        return (string)$this->elem;
    }

    /**
     * Array attr to string
     * @return string
     */
    protected function buildAttr(): string
    {
        $attr = "";
        if (count($this->attr) > 0) {
            foreach ($this->attr as $k => $v) {
                $attr .= " {$k}";
                if (!is_null($v)) {
                    $attr .= "=\"{$v}\"";
                }
            }
        }
        return $attr;
    }

    /**
     * Clone/Static
     * @return static|false
     */
    public function withElement()
    {
        if (!is_null($this->elem)) {
            return clone $this;
        }
        return false;
    }
}
