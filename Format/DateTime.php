<?php 
/**
 * @Package: 	PHPFuse Format date class with translations
 * @Author: 	Daniel Ronkainen
 * @Licence: 	The MIT License (MIT), Copyright © Daniel Ronkainen
 				Don't delete this comment, its part of the license.
 * @Version: 	1.0.0
 */
namespace PHPFuse\Output\Format;

use DateTime as MainDateTime;
use DateTimeZone;

class DateTime extends MainDateTime implements FormatInterface {

	const DEFAULT_LANG = "sv"; // Default
	const LANG = [
		"sv" => [
			"Jan", "Feb", "Mar", "Apr", "Maj", "Jun", "Jul", "Aug", "Okt", "Sep", "Nov", "Dec", 
			"Januari", "Februari", "Mars", "April", "Maj", "Juni", "Juli", "Augusti", "Oktober", "September", "November", "December",
			"Måndag", "Tisdag", "Onsdag", "Torsdag", "Fredag", "Lördag", "Söndag"
		],
		"en" => [
			"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Oct", "Sep", "Nov", "Dec", 
			"January", "February", "March", "April", "May", "June", "July", "August", "October", "September", "November", "December",
			"Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"
		]
	];

	private $lang;
	private $translations = array();


	function __construct(string $datetime = "now", ?DateTimeZone $timezone = null) {
		parent::__construct($datetime, $timezone);
		$this->translations = static::LANG;
	}

	static function value(string $value): \DateTime 
	{
		$inst = new self($value);
		return $inst;
	}

	function getTranslations() {
		return $this->translations;
	}
	
	function setTranslation(string $key, array $arr) {
		$this->translations[$key] = $arr;
		return $this;
	}

	function setLanguage(string $lang) {
		if(!isset($this->translations[$lang])) {
			throw new \Exception("Translation for the language \"{$lang}\" does not exists! You can add custom translation with @setTranslation method.", 1);
		}
		$this->lang = $lang;
		return $this;
	}

	function clone() {
		return clone $this;
	}

	function format($format): string 
	{
		$format = parent::format($format);
		return $this->translate($format);
	}


	private function langKey() {
		return (!is_null($this->lang)) ? $this->lang : $this::DEFAULT_LANG;
	}

	private function translate(string $format) {

		$k = $this->langKey();		
		if(isset($this::LANG[$k])) {
			return str_replace($this::LANG['en'], $this::LANG[$k], $format);
		}
		return $format;
	}

}
