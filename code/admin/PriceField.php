<?php
/**
 * Price field for managing prices.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class PriceField extends CurrencyField {

	/**
	 * Render field with custom template
	 * 
	 * @param array $properties
	 */
	public function FieldHolder($properties = array()) {

		$shopConfig = ShopConfig::current_shop_config();
		$properties = array_merge($properties, array(
			'BaseCurrency' => $shopConfig->BaseCurrency
		));

		$obj = ($properties) ? $this->customise($properties) : $this;
		return $obj->renderWith('PriceField_holder');
	}

	/**
	 * Set value of the field with explicitly formatted numbers.
	 * 
	 * @param mixed $val
	 */
	public function setValue($val) {
		if(!$val) $val = 0.00;
		$this->value = number_format((double)preg_replace('/[^0-9.\-]/', '', $val), 2);
		return $this;
	}

}