<?php 
/**
 * @Package: 	PHPFuse - Output Container
 * @Author: 	Daniel Ronkainen
 * @Licence: 	The MIT License (MIT), Copyright © Daniel Ronkainen
 				Don't delete this comment, its part of the license.
 * @Version: 	2.0.1
 */

namespace PHPFuse\Output;

use PHPFuse\Container\Interfaces\ContainerInterface;
use Exception;

use PHPFuse\DTO\Format\Str; 
use PHPFuse\DTO\Traverse;
use PHPFuse\Output\Dom\Document;
use PHPFuse\Output\Dom\Element;

class SwiftRender {

	const VIEWS = ["index", "buffer", "view", "partial"];

	private $file;
	private $ending = "php";
	private $buffer;
	private $index;
	private $view;
	private $partial;
	private $get;
	private $arg; // Default args
	private $arguments; // Merge with defualt args
	private $dir;
	private $bind;
	private $bindView;
	private $bindArr;
	private $container;

	/**
	 * Used to build output buffer;
	 */
	function __construct() {
		
	}

	/**
	 * Pass a container class instance of ContainerInterface, that can be used with in your templates
	 * @param ContainerInterface $container
	 */
	function setContainer(ContainerInterface $container): void 
	{
		$this->container = $container;
	}

	/**
	 * Get container instance
	 * @return ContainerInterface
	 */
	function getContainer(): ContainerInterface
	{
		return $this->container;
	}

	/**
	 * Set a default file ending (".php" is pre defined)
	 * @param self
	 */
	function setFileEnding(string $ending): self 
	{
		$this->ending = $ending;
		return $this;
	}

	/**
	 * Customize template file. 
	 * (Call this if you want to bind for multiple file to same partial)
	 * @param self
	 */
	function setFile(string $file): self 
	{
		$this->file = $file;
		return $this;
	}

	/**
	 * Set dir path to index files
	 * @param  string $type Template type (index/partial/view)
	 * @param  string $dir  Dir path
	 * @return self
	 */
	function setIndexDir(string $dir): self 
	{
		$dir = Str::value($dir)->trailingSlash()->get();
		$this->dir["index"] = $dir;
		return $this;
	}

	/**
	 * Set dir path to view files
	 * @param  string $type Template type (index/partial/view)
	 * @param  string $dir  Dir path
	 * @return self
	 */
	function setViewDir(string $dir): self 
	{
		$dir = Str::value($dir)->trailingSlash()->get();
		$this->dir["view"] = $dir;
		return $this;
	}

	/**
	 * Set dir path to buffer files (ONLY used if you have binded a view and setViewDir is empty!)
	 * @param  string $dir  Dir path
	 * @return self
	 */
	function setBufferDir(string $dir): self 
	{
		$dir = Str::value($dir)->trailingSlash()->get();
		$this->dir["buffer"] = $dir;
		return $this;
	}

	/**
	 * Set dir path to partial files
	 * @param  string $type Template type (index/partial/view)
	 * @param  string $dir  Dir path
	 * @return self
	 */
	function setPartialDir(string $dir): self 
	{
		$dir = Str::value($dir)->trailingSlash()->get();
		$this->dir["partial"] = $dir;
		return $this;
	}

	/**
	 * Create a index view
	 * @param  string $file Filename
	 * @return self
	 */
	function setIndex(string|callable $file): self 
	{
		if(is_null($this->file) && is_string($file)) $this->setFile($file);
		$func = $this->build($file);
		$this->index = $func;
		return $this;
	}

	/**
	 * Create a buffer/factory outout
	 * @return self
	 */
	function setBuffer(string $output): self 
	{
		$this->buffer = function() use($output) {
			echo $output;
		};
		return $this;
	}

	/**
	 * Create a Main view
	 * @param  string $file Filename
	 * @param  array  $args Pass on argummets to template
	 * @return self
	 */
	function setView(string|callable $file, array $args = array()): self 
	{
		if(is_null($this->file) && is_string($file)) $this->setFile($file);
		$func = $this->build($file, $args);
		$this->view = $func;
		return $this;
	}


	function withView(string $file, array $args = array()): self 
	{
		$inst = clone $this;
		$inst->setView($file, $args);
		return $inst->view();
	}

	/**
	 * Create a partial view
	 * @param  string $key  Partal key, example: ("sidebar", "breadcrumb")
	 * @param  string $file Filename
	 * @param  array  $args Pass on argummets to template
	 * @return self
	 */
	function setPartial(string $key, array $args = array()): self 
	{
		if(is_null($this->file)) $this->setFile($key);
		$func = $this->build($this->file, $args);
		$this->partial[$key] = $func;
		return $this;
	}
	
	/**
	 * Bind a View to a HTTP status reponse code
	 * @param  array  $statusArr 	Array with responese that will be bind to View (Array(404, 410...))
	 * @param  string $file      	View file
	 * @param  array  $args      	Pass on arguments/data to be used in view
	 * @return self
	 */
	function bindToBody(string $key, array $bindArr, array $args = array()): self 
	{
		$this->setFile($key);
		$func = $this->build($this->file, $args);
		$this->bind[$key] = $func;
		$this->bindArr[$key] = $bindArr;
		return $this;
	}

	/**
	 * IF find in specified Bind Array the it will return the view
	 * @param  string|int|float $find
	 * @return [type]       [description]
	 */
	function findBind($find, bool $overwrite = false): void 
	{
		if(!is_null($this->bindArr) && ($overwrite || is_null($this->bindView))) foreach($this->bindArr as $get => $arr) {
			if(in_array($find, $arr)) {
				$this->get = "bindView";
				$this->bindView = $this->bind[$get];
				break;
			}
		}
	}

	/**
	 * Prepare index for return
	 * @param  boolean $args Overwrite arguments
	 * @return self
	 */
	function index(?array $args = NULL): self 
	{
		if(!is_null($args)) $this->arg = $args;
		$this->get = "index";
		return $this;
	}

	/**
	 * Prepare buffer for return
	 * @param  boolean $args Overwrite arguments
	 * @return self
	 */
	function buffer(): self 
	{
		$this->get = "buffer";
		return $this;
	}

	/**
	 * Prepare view for return
	 * @param  array|null $args Overwrite arguments
	 * @return slef
	 */
	function view(?array $args = NULL): self 
	{
		if(!is_null($args)) $this->arg = $args;
		$this->get = "view";
		return $this;
	}

	


	/**
	 * Prepare partial for return
	 * @param  string $key select partial to read
	 * @return self
	 */
	function partial(string $key): self 
	{
		$this->arg = $key;
		$this->get = "partial";
		return $this;
	}

	/**
	 * Return prepared view
	 * @param  array|null $args merge args
	 * @return string
	 */
	final function get(?array $args = NULL): string 
	{
		if(!is_null($this->bindView)) {
			if(($b = $this->existAtGet("buffer")) || ($v = $this->existAtGet("index"))) {
				if($b) $this->buffer = $this->bindView;
				if($v) $this->view = $this->bindView;
			}	
		}

		$output = $this->{$this->get};

		// Will merge/replace arguments with current/deafult arguments
		if(is_array($args)) $args = array_merge($this->arguments, $args);

		ob_start();
		if(!is_null($this->arg)){
			if(is_array($output)) {				
				if(isset($output[$this->arg])) $output[$this->arg]($args);
			}
		} else {
			if(is_null($output)) {
				throw new Exception("Expecting the \"{$this->get}\" view.", 1);
			} else {
				$output($args);
			}		
		}

		$this->arg = $this->get = NULL;
		$output = ob_get_clean();

		return (string)$output;	
	}

	/**
	 * Build and Contain template and data until it's executed, 
	 * this means that code is prepared and will not take any extra memory if view would not be called.
	 * So you can if you want prepare a bunch of partial views and just call the the ones you want
	 * @param  string $file the filename
	 * @param  array  $args  Pass arguments to template
	 * @return callable
	 */
	private function build(string|callable $file, array $args = array()): callable 
	{
		$this->arguments = $args;
		$func = function($a) use($file, $args) {
			if(($dir = ($this->dir[$this->get] ?? NULL)) || !is_null($dir)) {

				if(is_callable($file)) {
					$out = $file($this, $args);
					if(is_string($out)) echo $out;

				} else {

					$filePath = "{$dir}{$file}.{$this->ending}";
					if(is_string($filePath) && is_file($filePath)) {
						if(is_array($a) && count($a) > 0) $args = $a;
						$obj = Traverse::value($args);
						include($filePath);

					} else {
						throw new Exception("Could not require template file add {$this->get}: {$dir}{$file}.", 1);
					}
				}

			} else {
				throw new Exception("You need to call @".str_replace("_", "", $this->get)."DIR and specify dir path for {$file}.", 1);
			}
		};

		$this->file = NULL;
		return $func;
	}

	/**
	 * Check if partial exists
	 * @param  string $key
	 * @return bool
	 */
	function partialExists($key): bool
	{
		return (bool)isset($this->partial[$key]);
	}

	/**
	 * Check if view exists
	 * @param  string $key
	 * @return bool
	 */
	function exists(string $key): bool
	{
		return (bool)(in_array($key, $this::VIEWS) && isset($this->{$key}));
	}

	function dom(string $key): Document
	{
		return Document::dom($key);
	}

	function createTag(string $element, string $value, ?array $attr = NULL) {
		$inst = new Document();
		$el = $inst->create($element, $value)->attrArr($attr);
		return $el;
	}

	function isDoc($el): bool 
	{
		return (bool)($el instanceof Document || $el instanceof Element);
	}

	function isEl($el): bool 
	{
		return (bool)($el instanceof Element);
	}

	
	/**
	 * Check if view exists at row
	 * @param  string $key
	 * @return bool
	 */
	private function existAtGet(string $key): bool
	{
		return (bool)(isset($this->{$key}) && $this->get === $key);
	}

	
}
