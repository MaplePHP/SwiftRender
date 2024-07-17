<?php

/**
 * @Package:    MaplePHP - Output Container
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Output;

use MaplePHP\Cache\Cache;
use MaplePHP\Cache\Handlers\FileSystemHandler;
use MaplePHP\Cache\Interfaces\CacheInterface;
use MaplePHP\Container\Interfaces\ContainerInterface;
use Exception;
use BadMethodCallException;
use MaplePHP\DTO\Format\Arr;
use MaplePHP\DTO\Format\Str;
use MaplePHP\DTO\Traverse;
use MaplePHP\Output\Dom\Document;
use MaplePHP\Output\Dom\Element;

class SwiftRender
{
    public const VIEWS = ["index", "buffer", "view", "partial"];

    private ?string $file = null;
    private string $ending = "php";
    private $buffer;
    private $index;
    private $view;
    private $partial;
    private $partialKey;
    private $get;
    private $arg; // Default args
    private $dir;
    private $bind;
    private $bindView;
    private $bindArr;
    private $container;
    private ?CacheInterface $cache = null;

    /**
     * Used to build output buffer;
     */
    public function __construct()
    {
        $path = realpath(__DIR__ . "/../../../storage/caches");
        $this->cache = new Cache(new FileSystemHandler($path));
    }

    /**
     * Access the container inside a template file
     * @param  string $method
     * @param  array $args
     * @return ContainerInterface
     */
    public function __call(string $method, array $args): ContainerInterface
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
    public function provider(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get container instance
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->provider();
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
     * Set dir path to buffer files (ONLY used if you have bound a view and setViewDir is empty!)
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
     * Create an index view
     * @param string|callable $file Filename
     * @return self
     * @throws Exception
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
     * Create a buffer/factory output
     * @param string $output
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
     * @param string|callable $file Filename
     * @param array $args Pass on arguments to template
     * @return self
     * @throws Exception
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
     * @param string $file [description]
     * @param array $args [description]
     * @return self
     * @throws Exception
     */
    public function withView(string $file, array $args = array()): self
    {
        $inst = clone $this;
        $inst->setView($file, $args);
        return $inst->view();
    }

    /**
     * Create a partial view
     * @param string $partial partial/key
     * @param array $args Args
     * @param int|false $cacheTime Cache view
     * @return SwiftRender
     * @throws Exception
     */
    public function setPartial(string $partial, array $args = array(), int|false $cacheTime = false): self
    {
        $this->partialKey = $partial;
        $keys = $this->selectPartial($partial, $file);
        if (is_null($this->file)) {
            $this->setFile($file);
        }
        $func = $this->build($this->file, $args, $partial, $cacheTime);
        $this->partial[$keys[0]][$keys[1]] = $func;

        return $this;
    }

    public function hasPartial($partial): bool
    {
        $keys = $this->selectPartial($partial);
        return isset($this->partial[$keys[0]][$keys[1]]);
    }

    /**
     * Unset a set partial
     * @param string $key Partial key, example: ("sidebar", "breadcrumb")
     * @return void
     */
    public function unsetPartial(string $key): void
    {
        if (isset($this->partial[$key])) {
            unset($this->partial[$key]);
        }
    }

    /**
     * Bind a View to an HTTP status repose code
     * @param string $key
     * @param array $bindArr
     * @param array $args Pass on arguments/data to be used in view
     * @return self
     * @throws Exception
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
     * IF find in specified Bind Array it will return the view
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
     * @param array|null $args merge args
     * @return string
     * @throws Exception
     */
    final public function get(?array $args = null): string
    {
        $this->buildView();
        $output = $this->{$this->get};
        ob_start();
        if (!is_null($this->arg)) {
            if (is_array($output)) {
                if (isset($output[$this->arg])) {
                    foreach ($output[$this->arg] as $part) {
                        $part($args);
                    }
                }
            }
        } else {
            if (is_null($output)) {
                throw new Exception("Expecting the \"$this->get\" view.", 1);
            } else {
                $output($args);
            }
        }

        $this->arg = $this->get = null;
        $output = ob_get_clean();

        return (string)$output;
    }

    /**
     * Check for expecting view dependencies
     * @return void
     */
    private function buildView(): void
    {
        if (!is_null($this->bindView)) {
            if ($this->existAtGet("buffer")) {
                $this->buffer = $this->bindView;
            }
            if ($this->existAtGet("index")) {
                $this->view = $this->bindView;
            }
        }
    }

    /**
     * Build and Contain template and data until it's executed,
     * this means that code is prepared and will not take any extra memory if view would not be called.
     * So you can if you want to prepare a bunch of partial views and just call the ones you want
     * @param string|callable $file the filename
     * @param array $args Pass arguments to template
     * @param string|null $partialKey
     * @param int|false $cacheTime
     * @return callable
     * @throws Exception
     */
    private function build(
        string|callable $file,
        array $args = array(),
        ?string $partialKey = null,
        int|false $cacheTime = false
    ): callable
    {

        if(is_null($this->cache) && $cacheTime !== false) {
            throw new Exception("Cache is not configured");
        }

        $func = function ($argsFromFile) use ($file, $args, $partialKey, $cacheTime) {
            if (($dir = ($this->dir[$this->get] ?? null)) || !is_null($dir)) {
                if (is_callable($file)) {
                    $out = $file($this, $args);
                    if (is_string($out)) {
                        echo $out;
                    }
                } else {

                    $throwError = true;
                    $missingFiles = array();
                    $files = explode("|", $file);

                    foreach($files as $file) {
                        if($file[0] === "!") {
                            $file = substr($file, 1);
                            $throwError = false;
                        }
                        $filePath = realpath("$dir$file.$this->ending");
                        if (is_file($filePath)) {
                            if (is_array($argsFromFile) && count($argsFromFile) > 0) {
                                $args = array_merge($args, $argsFromFile);
                            }

                            $this->getOutput($filePath, $partialKey, $args, $cacheTime);
                            break;

                        } else {
                            $missingFiles[] = $file;
                        }
                    }
                    if($throwError && count($missingFiles) > 0) {
                        throw new Exception("Could not require template \"$this->get\" files: ".implode(", ", $missingFiles).".", 1);
                    }
                }
            } else {
                $file = (is_string($file)) ? $file : "[Callable]";
                throw new Exception("You need to call @" . str_replace("_", "", (string)$this->get) .
                    "DIR and specify dir path for $file.", 1);
            }

        };

        $this->file = null;
        return $func;
    }


    /**
     * Get the view output
     * @param string $filePath
     * @param string|null $partialKey
     * @param array $args
     * @param int|false $cacheTime
     * @return void
     */
    private function getOutput(
        string $filePath,
        ?string $partialKey = null,
        array $args = [],
        int|false $cacheTime = false
    ): void
    {
        if($this->get == "partial" && !is_null($this->cache) && $cacheTime !== false) {
            $partialKey = str_replace(["!", "/"], ["", "_"], $partialKey);
            $updateTime = filemtime($filePath);
            $cacheKey = $partialKey."-".$updateTime;

            if(!$this->cache->has($cacheKey)) {
                $clear = Arr::value($this->cache->getAllKeys())->wildcardSearch("$partialKey-*")->get();
                if(count($clear)) {
                    $this->cache->deleteMultiple($clear);
                }

                ob_start();
                $this->inclRouterFileData($filePath, Traverse::value($args), $args);
                $out = ob_get_clean();
                $this->cache->set($cacheKey, $out, $cacheTime);
                echo $out;

            } else {
                echo $this->cache->get($cacheKey);
            }

        } else {
            $this->inclRouterFileData($filePath, Traverse::value($args), $args);
        }
    }

    /**
     * Check if partial exists
     * @param string $key
     * @return bool
     */
    public function partialExists(string $key): bool
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
        $extract = ($obj instanceof Traverse) ? $obj->toArray(function($row) {
            return Traverse::value($row);
        }) : $args;
        extract($extract);
        include($filePath);
    }

    public function dom(string $key): Document
    {
        return Document::dom($key);
    }

    /**
     * @throws Exception
     */
    public function createTag(string $element, string $value, ?array $attr = null)
    {
        $inst = new Document();
        $elem = $inst->create($element, $value);
        if (!($elem instanceof Element)) {
            throw new Exception("Could not find connection to Element instance", 1);
        }
        return $elem->attrArr($attr);
    }

    public function isDoc($elem): bool
    {
        return ($elem instanceof Document);
    }

    public function isEl($elem): bool
    {
        return ($elem instanceof Element);
    }


    /**
     * Get partial keys
     * @param  string      $partialKey
     * @param  string|null &$file
     * @return array
     */
    final protected function selectPartial(string $partialKey, ?string &$file = null): array
    {
        $key = $partialKey;
        $partial = explode(".", $partialKey);
        $file = $key2 = $key = $this->cleanKey($key);

        if (count($partial) > 1) {
            $key2 = $partial[1];
            $file = $key2 = $this->cleanKey($key2);
            $key = $partial[0];
            if (isset($partial[2])) {
                $key2 = "$key-$partial[2]";
            }
            if (($pos = strpos($key2, "|")) !== false) {
                $key2 = substr($key2, 0, $pos);
            }
        }
        return [$key, $key2];
    }

    /**
     * Clean key
     * @param  string $key
     * @return string
     */
    final protected function cleanKey(string $key): string
    {
        if(str_starts_with($key, "!")) {
            return substr($key, 1);
        }
        return $key;
    }
}
