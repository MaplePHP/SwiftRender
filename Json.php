<?php


declare(strict_types=1);

namespace PHPFuse\Output;

use PHPFuse\Output\Interfaces\JsonInterface;

class Json implements JsonInterface
{
    public $data = array("status" => 0, "error" => 0);
    public $fields = array();

    public function __construct()
    {
    }


    public function __toString()
    {
        return $this->encode();
    }

    /**
     * Merge array to json array
     * @param  array  $array
     * @return self
     */
    public function merge(array $array): self
    {
        $this->data = array_merge($this->data, $array);
        return $this;
    }

    /**
     * Overwrite whole json array
     * @param  array  $array
     * @return self
     */
    public function set($array): self
    {
        $this->data = $array;
        return $this;
    }

    /**
     * Merge array to json array
     * @param  array  $array
     * @return self
     */
    public function mergeTo(string $key, array $array): self
    {
        if (empty($this->data[$key])) {
            $this->data = array_merge($this->data, [$key => $array]);
        } else {
            $this->data[$key] = array_merge($this->data[$key], $array);
        }
        return $this;
    }


    /**
     * Merge string to json array
     * @param string $key   Set array key
     * @param mixed $value Set array value
     * @return self
     */
    public function add(string $key, $value): self
    {
        $this->data = array_merge($this->data, [$key => $value]);
        return $this;
    }


    /**
     * Merge string to json array
     * @param string $key   Set array key
     * @param mixed $value Set array value
     * @return self
     */
    public function item(...$args): array
    {
        $key = null;
        if (isset($args[0]) && !is_array($args[0])) {
            $key = array_shift($args);
            if (count($args) === 1) {
                $args = $args[0];
            }
        }
        $argumnets = (!is_null($key)) ? [[$key => $args]] : [...$args];
        $this->data = array_merge($this->data, $argumnets);
        return reset($argumnets);
    }

    /**
     * Merge string to json array
     * @param string $key   Set array key
     * @param mixed $value Set array value
     * @return self
     */
    public function field($key, $args): self
    {


        if (is_array($key)) {
            $key = key($key);
            $this->fields = array_merge($this->fields, [$key => [
                "type" => $key,
                ...$args
            ]]);
        } else {
            $this->fields = array_merge($this->fields, [$key => $args]);
        }


        return $this;
    }

    public function form($fields): self
    {
        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    /**
     * Reset
     * @return void
     */
    public function reset(?array $new = null): void
    {
        if (is_null($new)) {
            $new = array("status" => 0, "error" => 0);
        }

        $this->data = $new;
    }

    /**
     * same as @data method
     * @return mixed
     */
    public function get(?string $key = null)
    {
        return $this->data($key);
    }

    /**
     * Get current added json array data
     * @return mixed
     */
    public function data(?string $key = null)
    {
        if (!is_null($key)) {
            return $this->select($key);
        }
        return $this->data;
    }

    /**
     * Get data has HTML friendly string
     * @param  string $str select key (you can use comma sep. to traverse array)
     * @return string
     */
    public function output(string $key): ?string
    {
        $arr = $this->select($key);
        if ($get = self::encodeData($arr)) {
            return htmlentities($get);
        }
        return null;
    }

    /**
     * Convert json array to json string
     * @param  Bitmask  $options Bitmask
     * @param  integer $depth   Set the maximum depth. Must be greater than zero
     * @return json/bool (bool if could not load json data)
     */
    public function encode($options = JSON_UNESCAPED_UNICODE, $depth = 512): string
    {
        return self::encodeData($this->data, $options, $depth);
    }

    /**
     * Decode json data
     * @param  string  $json    Json data
     * @param  boolean $assoc   When TRUE, returned objects will be converted into associative arrays.
     * @return array/bool       Resturns as array or false if error occoured.
     */
    public function decode($json, $assoc = true): object
    {
        if ($array = json_decode($json, $assoc)) {
            return $array;
        }
        return false;
    }

    /**
     * Validate output
     * @return void
     */
    public function validate(): void
    {
        switch (self::error()) {
            case JSON_ERROR_DEPTH:
                throw new \Exception('The maximum stack depth has been exceeded', self::error());
                break;
            case JSON_ERROR_STATE_MISMATCH:
                throw new \Exception('Invalid or malformed JSON', self::error());
                break;
            case JSON_ERROR_CTRL_CHAR:
                throw new \Exception('Control character error, possibly incorrectly encoded', self::error());
                break;
            case JSON_ERROR_SYNTAX:
                throw new \Exception('Syntax error', self::error());
                break;
            case JSON_ERROR_UTF8:
                throw new \Exception('Malformed UTF-8 characters, possibly incorrectly encoded', self::error());
                break;
            case JSON_ERROR_RECURSION:
                throw new \Exception('One or more recursive references in the value to be encoded', self::error());
                break;
            case JSON_ERROR_INF_OR_NAN:
                throw new \Exception('One or more NAN or INF values in the value to be encoded', self::error());
                break;
            case JSON_ERROR_UNSUPPORTED_TYPE:
                throw new \Exception('A value of a type that cannot be encoded was given', self::error());
                break;
            case JSON_ERROR_INVALID_PROPERTY_NAME:
                throw new \Exception('A property name that cannot be encoded was given', self::error());
                break;
            case JSON_ERROR_UTF16:
                throw new \Exception('Malformed UTF-16 characters, possibly incorrectly encoded', self::error());
                break;
        }
    }

    /**
     * Json encode data
     * @param  array  $json     array to json
     * @param  opt  $flag       read php.net (or use the default)
     * @param  int $depth       read php.net
     * @return string|null
     */
    public static function encodeData(array $json, $flag = JSON_UNESCAPED_UNICODE, int $depth = 512): ?string
    {
        if (is_array($json) && count($json) > 0 && ($encode = json_encode($json, $flag, $depth))) {
            return $encode;
        }
        return null;
    }

    /**
     * Get last json error
     * @return int
     */
    public static function error(): int
    {
        return json_last_error();
    }

    /**
     * Travers slect data
     * @param  string $key
     * @return mixed
     */
    private function select(string $key)
    {
        $set = $this->data;
        $exp = explode(",", $key);
        foreach ($exp as $key) {
            if (isset($set[$key])) {
                $set = $set[$key];
            } else {
                return null;
            }
        }
        return $set;
    }
}
