<?php 
/**
 * @Package: 	PHPFuse - DOM Main class
 * @Author: 	Daniel Ronkainen
 * @Licence: 	The MIT License (MIT), Copyright © Daniel Ronkainen
 				Don't delete this comment, its part of the license.
 * @Version: 	1.0.0
 */

namespace PHPFuse\Output\Dom;

class Document {

	public const TAG_NO_ENDING = [
		"meta", "link", "img", "br", "hr", "input", "keygen", "param", "source", "track", "embed"
	];

	private $html = "";
	private $elements;
	private $el;

	private static $inst;

	/**
	 * Init DOM instance
	 * @param  string $key DOM access key
	 * @return new self
	 */
	static function dom(string $key) {
		if(empty(self::$inst[$key])) self::$inst[$key] = new self();
		return self::$inst[$key];
	}

	/**
	 * Create and bind tag to a key so it can be overwritten
	 * @param  string       $tag     HTML tag (without brackets)
	 * @param  string       $key     Bind tag to key
	 * @param  bool|boolean $prepend Prepend instead of append
	 * @return self
	 */
	function bindTag(string $tag, string $key, bool $prepend = false) {
		if($prepend) {
			$this->el = $this->createPrepend($tag, NULL, $key);
		} else {
			$this->el = $this->create($tag, NULL, $key);
		}
		return $this->el;
	}

	

	/**
	 * Create (append) element
	 * @param  string $element HTML tag (without brackets)
	 * @param  string $value   add value to tag
	 * @return self
	 */
	function create($element, $value = NULL, ?string $bind = NULL) {
		$inst = new Element($element, $value);

		if(!is_null($bind)) {
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
	function createPrepend(string $element, ?string $value = NULL, ?string $bind = NULL) {
		$inst = new Element($element, $value);
		if(is_null($this->elements)) $this->elements = array();

		if(!is_null($bind)) {
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
	function getElement($k) {
		return ($this->elements[$k] ?? NULL);
	}

	/**
	 * Get all elements
	 * @return array
	 */
	function getElements() {
		return $this->elements;
	}

	function getTag(string $key) {
		return ($this->el[$key] ?? NULL);
	}

	/**
	 * Execute and get Dom/document
	 * @param  callable|null $call Can be used to manipulate element within feed
	 * @return string
	 */
	function execute(?callable $call = NULL) {
		$this->html = "";
		if(is_array($this->elements)) {
			$this->build($this->elements, $call);
		}
		return $this->html;
	}

	/**
	 * Get get Dom/document (You need to execute first!)
	 * @return string
	 */
	function get() {
		return $this->html;
	}

	/**
	 * Build document
	 * @param  array         $arr  elements
	 * @param  callable|null $call Can be used to manipulate element within feed
	 */
	private function build(array $arr, ?callable $call = NULL) {
		foreach($arr as $k => $a) {
			$hasNoEnding = in_array($a->getEl(), $this::TAG_NO_ENDING);

			if(!is_null($call)) $call($a, $k, $hasNoEnding);

			$this->html .= "\t<".$a->getEl().$a->buildAttr().">";
			if(!$hasNoEnding) $this->html .= $a->getValue();
			if(isset($a->elements)) {
				$this->build($a->elements, $call);
			}
			if(!$hasNoEnding) $this->html .= "</".$a->getEl().">\n";
			if($hasNoEnding) $this->html .= "\n";
		}
	}

}




?>