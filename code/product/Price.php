<?php

class Price extends Money {

	protected $symbol;

	public function setSymbol($symbol) {
		$this->symbol = $symbol;
	}

	public function getSymbol() {
		return $this->symbol;
	}

	public function Nice($options = array()) {

		if ($this->symbol) {

			$amount = $this->getAmount();
			if(!isset($options['display'])) $options['display'] = Zend_Currency::NO_SYMBOL;
			if(!isset($options['currency'])) $options['currency'] = $this->getCurrency();
			if(!isset($options['symbol'])) $options['symbol'] = $this->currencyLib->getSymbol($this->getCurrency(), $this->getLocale());
			return (is_numeric($amount)) ? $this->symbol . $this->currencyLib->toCurrency($amount, $options) : $this->symbol . '';
		}
		else {

			$amount = $this->getAmount();
			if(!isset($options['display'])) $options['display'] = Zend_Currency::USE_SYMBOL;
			if(!isset($options['currency'])) $options['currency'] = $this->getCurrency();
			if(!isset($options['symbol'])) $options['symbol'] = $this->currencyLib->getSymbol($this->getCurrency(), $this->getLocale());
			return (is_numeric($amount)) ? $this->currencyLib->toCurrency($amount, $options) : '';
		}
	}
}