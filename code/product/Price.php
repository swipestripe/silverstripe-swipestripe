<?php
require_once 'Zend/Locale/Math.php';

class Price extends Money {

	protected $symbol;

	public function setSymbol($symbol) {
		$this->symbol = $symbol;
		return $this;
	}

	public function getSymbol($currency = null, $locale = null) {
		return $this->symbol;
	}

	public function getAmount() {
		return Zend_Locale_Math::round($this->amount, 2);
	}

	public function Nice($options = array()) {

		if ($this->symbol) {

			$amount = $this->getAmount();
			if(!isset($options['display'])) $options['display'] = Zend_Currency::NO_SYMBOL;
			if(!isset($options['currency'])) $options['currency'] = $this->getCurrency();
			if(!isset($options['symbol'])) $options['symbol'] = $this->currencyLib->getSymbol($this->getCurrency(), $this->getLocale());

			if (is_numeric($amount)) {
				if ($amount < 0) {
					return '- ' . $this->symbol . $this->currencyLib->toCurrency(abs($amount), $options);
				}
				else {
					return $this->symbol . $this->currencyLib->toCurrency($amount, $options);
				}
			}
			else {
				return $this->symbol . '';
			}
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