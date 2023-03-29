<?php 
/**
 * @Package: 	PHPFuse Format date class with translations
 * @Author: 	Daniel Ronkainen
 * @Licence: 	The MIT License (MIT), Copyright © Daniel Ronkainen
 				Don't delete this comment, its part of the license.
 * @Version: 	1.0.0
 */
namespace PHPFuse\Output\Format;

class DateTime extends \DateTime implements FormatInterface {

	private $_lang;

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

	static function value($value): \DateTime 
	{
		return new \DateTime($value);
	}

	function get($key) {
		return $this->format();
	}

	private function _langKey() {
		return (!is_null($this->_lang)) ? $this->_lang : $this::DEFAULT_LANG;

	}

	function translate($lang) {
		$this->_lang = $lang;
		return $this;
	}

	private function _translate(string $format) {

		$k = $this->_langKey();		
		if(isset($this::LANG[$k])) {
			return str_replace($this::LANG['en'], $this::LANG[$k], $format);
		}
		return $format;
	}

	function format($key) {
		$format = parent::format($key);
		return $this->_translate($format);
	}

	function clone() {
		return clone $this;
	}

}

?>