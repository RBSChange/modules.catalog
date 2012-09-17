<?php
/**
 * @method catalog_PriceFormatter getInstance()
 */
class catalog_PriceFormatter extends change_Singleton
{
	/**
	 * @return void
	 */
	public static function clearInstance()
	{
		self::clearInstanceByClassName(get_called_class());
	}
	
	/**
	 * @param string $currencyCode
	 * @param string $lang
	 * @param string $symbolPosition
	 * @return string
	 */
	public function getFormat($currencyCode, $lang = null,  $symbolPosition = null)
	{
		if ($lang === null)
		{
			$lang = RequestContext::getInstance()->getLang();
		}
		
		if ($symbolPosition === null)
		{
			$symbolPosition = $this->getSymbolPositionByLang($lang);
		}
		
		$currencySymbol = $this->getSymbolByCode($currencyCode);
		if ($currencySymbol === null)
		{
			return '%s';
		}
		elseif ($symbolPosition === 'left')
		{
			return $currencySymbol . ' %s';
		}
		return '%s '. $currencySymbol;
	}
	
	/**
	 * @param float $value
	 * @param string $format
	 * @param string $currencyCode
	 * @param string $lang
	 * @return string
	 */
	public function applyFormat($value, $format, $currencyCode,  $lang = null)
	{
		if (f_util_StringUtils::isEmpty($format))
		{
			return $this->format($value, $currencyCode, $lang);
		}
		if (!is_numeric($value)) {return $value;}
				
		if ($lang === null)
		{
			$lang = RequestContext::getInstance()->getLang();
		}
		$d = $this->getDecimalByCode($currencyCode);
		$value = $this->roundDefault($value, $currencyCode, $lang);	
		return sprintf($format, number_format($value, $d, ',', ' '));
	}
	
	/**
	 * @param float $value
	 * @param string $currencyCode
	 * @param string $lang
	 * @param string $symbolPosition
	 * @return string
	 */
	public function format($value, $currencyCode, $lang = null, $symbolPosition = null)
	{
		if ($lang === null)
		{
			$lang = RequestContext::getInstance()->getLang();
		}
		
		if ($symbolPosition === null)
		{
			$symbolPosition = $this->getSymbolPositionByLang($lang);
		}		
		if (class_exists('NumberFormatter', false))
		{
			// We have intl support
			$locale = LocaleService::getInstance()->getLCID($lang);
			$nf = new NumberFormatter($locale, NumberFormatter::CURRENCY);
			return $nf->formatCurrency($value, $currencyCode);
		}
		return $this->formatDefault($value, $currencyCode, $lang, $symbolPosition);
	}	
	
	/**
	 * @param float $value
	 * @param string $currencyCode
	 * @param string $lang
	 * @return double
	 */
	public function round($value, $currencyCode = null, $lang = null)
	{
		if ($lang === null)
		{
			$lang = RequestContext::getInstance()->getLang();
		}
		if ($currencyCode === null)
		{
			$currencyCode = 'EUR';
		}
		return $this->roundDefault($value, $currencyCode, $lang);
	}
		
	/**
	 * @param string $currencyCode
	 * @return string|null
	 */
	public function getSymbol($currencyCode)
	{
		if (f_util_StringUtils::isNotEmpty($currencyCode))
		{
			return $this->getSymbolByCode($currencyCode);
		}
		return null;
	}	
		
	protected $symbolArray = array();
	
	protected function getSymbolByCode($currencyCode)
	{
		if (!array_key_exists($currencyCode, $this->symbolArray))
		{
			$this->symbolArray[$currencyCode] = catalog_CurrencyService::getInstance()->getSymbolByCode($currencyCode);
		}
		return $this->symbolArray[$currencyCode];
	}
	
	protected function getSymbolPositionByLang($lang)
	{
		return ($lang === 'de' || $lang === 'en') ? 'left' : 'right';
	}
	
	protected function getDecimalByCode($currencyCode)
	{
		return ($currencyCode === 'JPY') ? 0 : 2;
	}
	
	/**
	 * @param double $value
	 * @param string $currencyCode
	 * @param string $lang
	 * @param string $symbolPosition
	 * @return string
	 */
	protected function formatDefault($value, $currencyCode, $lang, $symbolPosition)
	{
		if (!is_numeric($value)) {return $value;}
		
		$currencySymbol = $this->getSymbolByCode($currencyCode);
		if ($currencySymbol === null) {$currencySymbol = '?';}		
		$d = $this->getDecimalByCode($currencyCode);
	
		$value = $this->roundDefault($value, $currencyCode, $lang);
		if ($symbolPosition === 'left')
		{
			return $currencySymbol . ' ' . number_format($value, $d, ',', ' ');
		}
		return number_format($value, $d, ',', ' ') . ' '. $currencySymbol;
	}
	
	/**
	 * @param double $value
	 * @param string $currencyCode
	 * @param string $lang
	 * @return double
	 */
	protected function roundDefault($value, $currencyCode, $lang)
	{
		$d = $this->getDecimalByCode($currencyCode);
		return round($value, $d);
	}
}