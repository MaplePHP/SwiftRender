<?php 
/**
 * @Package: 	PHPFuse - The main traverse class
 * @Author: 	Daniel Ronkainen
 * @Licence: 	The MIT License (MIT), Copyright © Daniel Ronkainen
 				Don't delete this comment, its part of the license.
 * @Version: 	1.0.0
 */

namespace PHPFuse\Output;

use PHPFuse\Output\Format;

class Traverse {

	protected $row; // Use row to access current instance (access inst/object)
	protected $raw; // Use raw to access current instance data (access array)
	
	/**
	 * Init intance
	 * @param  array|object $data [description]
	 * @return [type]       [description]
	 */
	static function value($data, $raw = NULL) {
		$inst = new static();
		$inst->raw = $raw;

		if(is_array($data) || is_object($data)) {
			foreach($data as $k => $v) $inst->{$k} = $v;
		}
		return $inst;
	}

	/**
	 * Traverse factory 
	 * If you want 
	 * @return self
	 */
	function __call($a, $b) {
		if(!is_null(($this->{$a} ?? NULL))) {
			$this->row = $this->{$a};
			$this->raw = $this->row;
			if(is_array($this->row) || is_object($this->row)) {
				return $this::value($this->row, $this->raw);
			}
			if(count($b) > 0) {
				$r = new \ReflectionClass("PHPFuse\\Output\\Format\\".$b[0]);
				$instance = $r->newInstanceWithoutConstructor();
				return $instance->value($this->row);
			}
		}
		return $this;
	}

	function count() {
		return (is_array($this->raw) ? count($this->raw) : 0);
	}

	/**
	 * Access incremental array
	 * @param  string   $key      Column name
	 * @param  callable $callback Access array row in the callbacks argumnet 1
	 * @return self
	 */
	function fetch(?callable $callback = NULL) {
		$new = array();
		foreach($this->raw as $key => $row) {
			if(is_array($row) || is_object($row)) {
				// Incremental -> object
				$r = $this::value($row);

			} else {
				// Incremental -> value
				$r = Format\Str::value($row);
			}
			$new[$key] = $r;
			if(!is_null($callback)) $callback($r, $key, $row);
		}
		$this->row = $new;
		return $this;
	}

	/**
	 * Get/return result
	 * @return inherit
	 */
	function get() {
		return $this->row;
	}

}
