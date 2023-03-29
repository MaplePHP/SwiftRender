<?php 
/**
 * @Package: 	PHPFuse - Format json to media output
 * @Author: 	Daniel Ronkainen
 * @Licence: 	The MIT License (MIT), Copyright © Daniel Ronkainen
 				Don't delete this comment, its part of the license.
 * @Version: 	1.0.0
 */

namespace PHPFuse\Output\Format;

class Media implements FormatInterface {

	public $file;
	public $thumb;
	public $width;
	public $height;
	public $alt;
	public $title;
	public $src;

	private $noCache;
	private $timestamp;
	private $attr = array();
	private $traverse;
	
	function __construct(\PHPFuse\Http\traverse $traverse) {
		$this->traverse = $traverse;
		$this->timestamp = time();
		foreach($traverse as $k => $v) $this->{$k} = $v;	
	}

	static function value($value) {
		return new self($value);
	}

	function get() {
		
	}

	function traverse(\PHPFuse\Http\traverse $traverse) {
		return $this->traverse;
	}

	function setAttr(array $arr) {
		$this->attr = array_merge($this->attr, $arr);
		return $this;
	}

	protected function _getAttr() {
		$out = "";
		foreach($this->attr as $k => $v) $out .= " {$k}=\"{$v}\"";
		return $out;
	}

	function setWidth(int $width) {
		$this->width = $width;
		return $this;
	}

	function setHeight(int $height) {
		$this->height = $height;
		return $this;
	}

	function setAlt(int $alt) {
		$this->alt = $alt;
		return $this;
	}

	function setTitle(int $title) {
		$this->title = $title;
		return $this;
	}

	function setSrc(string $src) {
		$this->src = $src;
		return $this;
	}

	function path(?string $add = NULL) {
		return \Entity\URL::_upload($this->path.$add);
	}

	function file() {
		return $this->path($this->file);
	}

	function thumb() {
		return $this->path($this->thumb);
	}

	function noCache() {
		$this->noCache = "?v=".$this->timestamp;
		return $this;
	}

	function src() {
		return ((is_null($this->src)) ? $this->src : $this->file).$this->noCache;
	}

	function str() {

	}	

	function img() {
		$this->setAttr([
			"width" => $this->width,
			"height" => $this->height,
			"src" => $this->src(),
			"alt" => $this->alt,
			"title" => $this->title,
		]);
		return '<img'.$this->_getAttr().'>';
	}

	function svg() {
		$this->setAttr([
			"width" => $this->width,
			"height" => $this->height,
			"src" => $this->src(),
			"title" => $this->title,
		]);
		return '<object type="image/svg+xml"'.$this->_getAttr().'></object>';
	}


	function preview() {
		switch($this->filetype) {
			case "image":
				return $this->img();
			break;
			case "svg":
				return $this->svg();
			break;
			case "video":
				return "<svg width=\"30\" height=\"18\" viewBox=\"0 0 30 18\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M21 6l8-5v16l-8-5V6zM1 1v16h20V1H1z\" stroke=\"#000\" stroke-width=\"2\" fill=\"none\" fill-rule=\"evenodd\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/></svg>";
			break;
			case "audio":
				return '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><g stroke="#000"><path d="M11 25V6l13-3v20M11 13l13-3"/><ellipse cx="7" cy="25" rx="4" ry="5"/><ellipse cx="20" cy="23" rx="4" ry="5"/></g></svg>';
			break;
			default: // FILE
				return "<svg width=\"22\" height=\"30\" viewBox=\"0 0 22 30\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M1 1v28h20V9l-8-8H1zm12 0v8h8l-8-8z\" stroke=\"#000\" stroke-width=\"2\" fill=\"none\" fill-rule=\"evenodd\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/></svg>";
			break;
		}
	}
	
}