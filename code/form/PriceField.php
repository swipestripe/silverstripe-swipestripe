<?php

class PriceField extends CurrencyField {

	public function FieldHolder($properties = array()) {

		$shopConfig = ShopConfig::current_shop_config();
		$properties = array_merge($properties, array(
			'BaseCurrency' => $shopConfig->BaseCurrency
		));

		$obj = ($properties) ? $this->customise($properties) : $this;
		return $obj->renderWith('PriceField_holder');
	}

	public function setValue($val) {
		if(!$val) $val = 0.00;
		$this->value = number_format((double)preg_replace('/[^0-9.\-]/', '', $val), 2);
		return $this;
	}

}