<?php

/**
 * @Package:    PHPFuse - Output Container
 * @Author:     Daniel Ronkainen
 * @Licence:    The MIT License (MIT), Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 * @Version:    2.0.1
 */

namespace PHPFuse\Output;

use PHPFuse\Container\Interfaces\ContainerInterface;
use Exception;
use BadMethodCallException;

use PHPFuse\DTO\Format\Str;
use PHPFuse\DTO\Traverse;
use PHPFuse\Output\Dom\Document;
use PHPFuse\Output\Dom\Element;

class SwiftRender
{
    public const VIEWS = ["index", "buffer", "view", "partial"];

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
    public function __construct()
    {
    }


    /**
     * This will make shortcuts to container.
     * @param  string $m [description]
     * @param  string $a [description]
     * @return ContainerInterface
     */
    public function __call($m, $a)
    {
        if (!is_null($this->container)) {
            if ($this->container->has($m)) {
                return $this->container->get($m, $a);
            } else {
                throw new BadMethodCallException('The method "'.$m.'" does not exist in the Container '.
                    'or the Class "'.static::class.'"!', 1);
            }
        }
    }

    /**
     * Pass a container class instance of ContainerInterface, that can be used with in your templates
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * Get container instance
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Set a default file ending (".php" is pre defined)
     * @param self
     */
    public function setFileEnding(string $ending): self
    {
        $this->ending = $ending;
        return $this;
    }

    /**
     * Customize template file.
     * (Call this if you want to bind for multiple file to same partial)
     * @param self
     */
    public function setFile(string $file): self
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
    public function setIndexDir(string $dir): self
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
    public function setViewDir(string $dir): self
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
    public function setBufferDir(string $dir): self
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
    public function setPartialDir(string $dir): self
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
    public function setIndex(string|callable $file): self
    {
        if (is_null($this->file) && is_string($file)) {
            $this->setFile($file);
        }
        $func = $this->build($file);
        $this->index = $func;
        return $this;
    }

    /**
     * Create a buffer/factory outout
     * @return self
     */
    public function setBuffer(string $output): self
    {
        $this->buffer = function () use ($output) {
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
    public function setView(string|callable $file, array $args = array()): self
    {
        if (is_null($this->file) && is_string($file)) {
            $this->setFile($file);
        }
        $func = $this->build($file, $args);
        $this->view = $func;
        return $this;
    }

    /**
     * Keep prev view immutable and create a new one.
     * @param  string $file [description]
     * @param  array  $args [description]
     * @return static
     */
    public function withView(string $file, array $args = array()): self
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
    public function setPartial(string $key, string|array $a = array(), array $b = array()): self
    {
        if (is_array($a)) {
            $b = $a;
            $partial = $key;
        } else {
            $partial = $a;
        }

        if (is_null($this->file)) {
            $this->setFile($key);
        }
        $func = $this->build($this->file, $b);
        $this->partial[$partial][] = $func;
        return $this;
    }

    /**
     * Unset a setted partial
     * @param  string $key Partal key, example: ("sidebar", "breadcrumb")
     * @return void
     */
    public function unsetPartial($key): void
    {
        if (isset($this->partial[$key])) {
            unset($this->partial[$key]);
        }
    }

    /**
     * Bind a View to a HTTP status reponse code
     * @param  array  $statusArr    Array with responese that will be bind to View (Array(404, 410...))
     * @param  string $file         View file
     * @param  array  $args         Pass on arguments/data to be used in view
     * @return self
     */
    public function bindToBody(string $key, array $bindArr, array $args = array()): self
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
    public function findBind($find, bool $overwrite = false): void
    {
        if (!is_null($this->bindArr) && ($overwrite || is_null($this->bindView))) {
            foreach ($this->bindArr as $get => $arr) {
                if (in_array($find, $arr)) {
                    $this->get = "bindView";
                    $this->bindView = $this->bind[$get];
                    break;
                }
            }
        }
    }

    /**
     * Prepare index for return
     * @param  boolean $args Overwrite arguments
     * @return self
     */
    public function index(?array $args = null): self
    {
        if (!is_null($args)) {
            $this->arg = $args;
        }
        $this->get = "index";
        return $this;
    }

    /**
     * Prepare buffer for return
     * @param  boolean $args Overwrite arguments
     * @return self
     */
    public function buffer(): self
    {
        $this->get = "buffer";
        return $this;
    }

    /**
     * Prepare view for return
     * @param  array|null $args Overwrite arguments
     * @return slef
     */
    public function view(?array $args = null): self
    {
        if (!is_null($args)) {
            $this->arg = $args;
        }
        $this->get = "view";
        return $this;
    }

    /**
     * Prepare partial for return
     * @param  string $key select partial to read
     * @return self
     */
    public function partial(string $key): self
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
    final public function get(?array $args = null): string
    {
        if (!is_null($this->bindView)) {
            if (($b = $this->existAtGet("buffer")) || ($v = $this->existAtGet("index"))) {
                if ($b) {
                    $this->buffer = $this->bindView;
                }
                if ($v) {
                    $this->view = $this->bindView;
                }
            }
        }

        $output = $this->{$this->get};

        // Will merge/replace arguments with current/deafult arguments
        if (is_array($args)) {
            $args = array_merge($this->arguments, $args);
        }

        ob_start();
        if (!is_null($this->arg)) {
            if (is_array($output)) {
                //if(isset($output[$this->arg])) $output[$this->arg]($args);
                if (isset($output[$this->arg])) {
                    foreach ($output[$this->arg] as $part) {
                        $part($args);
                    }
                }
            }
        } else {
            if (is_null($output)) {
                throw new Exception("Expecting the \"{$this->get}\" view.", 1);
            } else {
                $output($args);
            }
        }

        $this->arg = $this->get = null;
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
        $func = function ($a) use ($file, $args) {
            if (($dir = ($this->dir[$this->get] ?? null)) || !is_null($dir)) {
                if (is_callable($file)) {
                    $out = $file($this, $args);
                    if (is_string($out)) {
                        echo $out;
                    }
                } else {
                    $filePath = "{$dir}{$file}.{$this->ending}";
                    if (is_string($filePath) && is_file($filePath)) {
                        if (is_array($a) && count($a) > 0) {
                            $args = $a;
                        }
                        $obj = Traverse::value($args);
                        include($filePath);
                    } else {
                        throw new Exception("Could not require template file add {$this->get}: {$dir}{$file}.", 1);
                    }
                }
            } else {
                throw new Exception("You need to call @".str_replace("_", "", $this->get).
                    "DIR and specify dir path for {$file}.", 1);
            }
        };

        $this->file = null;
        return $func;
    }

    /**
     * Check if partial exists
     * @param  string $key
     * @return bool
     */
    public function partialExists($key): bool
    {
        return (bool)isset($this->partial[$key]);
    }

    /**
     * Check if view exists
     * @param  string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        return (bool)(in_array($key, $this::VIEWS) && isset($this->{$key}));
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

    public function dom(string $key): Document
    {
        return Document::dom($key);
    }
    public function createTag(string $element, string $value, ?array $attr = null)
    {
        $inst = new Document();
        $el = $inst->create($element, $value)->attrArr($attr);
        return $el;
    }

    public function isDoc($el): bool
    {
        return (bool)($el instanceof Document || $el instanceof Element);
    }

    public function isEl($el): bool
    {
        return (bool)($el instanceof Element);
    }

    /*
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
    */
}
