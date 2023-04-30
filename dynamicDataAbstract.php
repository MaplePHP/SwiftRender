<?php
/**
 * @Package: 	PHPFuse Dynamic data abstraction Class
 * @Author: 	Daniel Ronkainen
 * @Licence: 	The MIT License (MIT), Copyright © Daniel Ronkainen
 				Don't delete this comment, its part of the license.
 */

namespace PHPFuse\Output;

class dynamicDataAbstract {

	private $data;

	function __construct() {
        $this->data = new \stdClass();
    }

    function getData() {
    	return $this->data;
    }

    public function __set($key, $value)
    {
        $this->data->{$key} = $value;
    }

    public function __get($key)
    {
        return $this->data->{$key};
    }

}
