<?php

class PriceField extends CurrencyField {

	function setValue($val) {
		if(!$val) $val = 0.00;
		$this->value = number_format((double)preg_replace('/[^0-9.\-]/', '', $val), 2);
		return $this;
	}

}