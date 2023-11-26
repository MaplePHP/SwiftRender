<?php

namespace MaplePHP\Output\Interfaces;

interface JsonInterface
{
    /**
     * Merge string to json array
     * @param string $key   Set array key
     * @param mixed $value Set array value
     * @return self
     */
    public function add(string $key, $value): self;

    /**
     * Overwrite whole json array
     * @param  array  $array
     * @return self
     */
    public function set($array): self;

    /**
     * Merge array to json array
     * @param  array  $array
     * @return self
     */
    public function merge(array $array): self;

    /**
     * Merge array to json array
     * @param  array  $array
     * @return self
     */
    public function mergeTo(string $key, array $array): self;

    /**
     * same as @data method
     * @return mixed
     */
    public function get(?string $key = null);

    /**
     * Convert json array to json string
     * @param  int  $options Bitmask
     * @param  int $depth   Set the maximum depth. Must be greater than zero
     * @return string|null (bool if could not load json data)
     */
    public function encode(int $options = JSON_UNESCAPED_UNICODE, int $depth = 512): string|null;

    /**
     * Decode json data
     * @param  string  $json        Json data
     * @param  boolean $assoc       When TRUE, returned objects will be converted into associative arrays.
     * @return object|array|false   Resturns as array or false if error occoured.
     */
    public function decode($json, $assoc = true): object|array|false;

    /**
     * Validate output
     * @return void
     */
    public function validate(): void;
}
