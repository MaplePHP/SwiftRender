<?php

/**
 * @Package:    MaplePHP - Output Container
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Output;

use MaplePHP\Container\Interfaces\ContainerInterface;
use Exception;
use BadMethodCallException;
use MaplePHP\DTO\Format\Str;
use MaplePHP\DTO\Traverse;
use MaplePHP\Output\Dom\Document;
use MaplePHP\Output\Dom\Element;

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
    public function __call($method, $args): ContainerInterface
    {
        if (!is_null($this->container)) {
            if ($this->container->has($method)) {
                return $this->container->get($method, $args);
            }
        }

        throw new BadMethodCallException('The method "' . $method . '" does not exist in the Container ' .
                    'or the Class "' . static::class . '"!', 1);
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
     * @param string $ending
     * @return self
     */
    public function setFileEnding(string $ending): self
    {
        $this->ending = $ending;
        return $this;
    }

    /**
     * Customize template file.
     * (Call this if you want to bind for multiple file to same partial)
     * @param string $file
     * @return self
     */
    public function setFile(string $file): self
    {
        $this->file = $file;
        return $this;
    }

    /**
     * Set dir path to index files
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
     * @param  string|callable $file Filename
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
     * @param  string|callable $file Filename
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
     * @return self
     */
    public function withView(string $file, array $args = array()): self
    {
        $inst = clone $this;
        $inst->setView($file, $args);
        return $inst->view();
    }

    /**
     * Create a partial view
     * @param string $keyA          Filename/key
     * @param string|array $keyB    Args/filename
     * @param array  $keyC          Args
     */
    public function setPartial(string $keyA, string|array $keyB = array(), array $keyC = array()): self
    {
        $partial = $keyB;
        if (is_array($keyB)) {
            $keyC = $keyB;
            $partial = $keyA;
        }

        if (is_null($this->file)) {
            $this->setFile($keyA);
        }
        $func = $this->build($this->file, $keyC);
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
     * @param  string|int   $find
     * @param  bool     $overwrite
     * @return void
     */
    public function findBind(string|int $find, bool $overwrite = false): void
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
     * @param  array|null $args Overwrite arguments
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
     * @return self
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
        $this->buildView();
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

    private function buildView(): void
    {
        if (!is_null($this->bindView)) {
            $hasBuffer = $this->existAtGet("buffer");
            $hasIndex = $this->existAtGet("index");
            if ($hasBuffer) {
                $this->buffer = $this->bindView;
            }
            if ($hasIndex) {
                $this->view = $this->bindView;
            }
        }
    }

    /**
     * Build and Contain template and data until it's executed,
     * this means that code is prepared and will not take any extra memory if view would not be called.
     * So you can if you want prepare a bunch of partial views and just call the the ones you want
     * @param  string|callable $file the filename
     * @param  array  $args  Pass arguments to template
     * @return callable
     */
    private function build(string|callable $file, array $args = array()): callable
    {

        $this->arguments = $args;
        $func = function ($argsFromFile) use ($file, $args) {

            if (($dir = ($this->dir[$this->get] ?? null)) || !is_null($dir)) {
                if (is_callable($file)) {
                    $out = $file($this, $args);
                    if (is_string($out)) {
                        echo $out;
                    }
                } else {
                    $filePath = "{$dir}{$file}.{$this->ending}";
                    if (is_file($filePath)) {
                        if (is_array($argsFromFile) && count($argsFromFile) > 0) {
                            $args = $argsFromFile;
                        }
                        $this->inclRouterFileData($filePath, Traverse::value($args), $args);
                    } else {
                        throw new Exception("Could not require template file add {$this->get}: {$dir}{$file}.", 1);
                    }
                }
            } else {
                $file = (is_string($file)) ? $file : "[Callable]";
                throw new Exception("You need to call @" . str_replace("_", "", (string)$this->get) .
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
        return isset($this->partial[$key]);
    }

    /**
     * Check if view exists
     * @param  string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        return (in_array($key, $this::VIEWS) && isset($this->{$key}));
    }

    /**
     * Check if view exists at row
     * @param  string $key
     * @return bool
     */
    private function existAtGet(string $key): bool
    {
        return (isset($this->{$key}) && $this->get === $key);
    }

    /**
     * Include router file with router object data
     * @param  string $filePath
     * @param  object $obj
     * @param  array  $args
     * @psalm-suppress UnusedParam
     * @return void
     */
    private function inclRouterFileData(string $filePath, object $obj, array $args): void
    {
        include($filePath);
    }

    public function dom(string $key): Document
    {
        return Document::dom($key);
    }

    public function createTag(string $element, string $value, ?array $attr = null)
    {
        $inst = new Document();
        $elem = $inst->create($element, $value);
        if (!($elem instanceof Element)) {
            throw new \Exception("Could not find connection to Element instance", 1);
        }
        $elem = $elem->attrArr($attr);
        return $elem;
    }

    public function isDoc($elem): bool
    {
        return ($elem instanceof Document || $elem instanceof Element);
    }

    public function isEl($elem): bool
    {
        return ($elem instanceof Element);
    }
}
