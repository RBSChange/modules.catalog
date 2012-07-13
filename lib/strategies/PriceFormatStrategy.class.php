<?php

interface catalog_PriceFormatStrategy
{
	/**
	 * Formats the value as a price using the currency identified by currencyCode in the given lang
	 * 
	 * @param double $value
	 * @param string $currencyCode
	 * @param string $lang
	 * @param string $symbolPosition
	 */
	function format($value, $currencyCode, $lang, $symbolPosition);
	
	/**
	 * @param double $value
	 * @param string $currencyCode
	 * @param string $lang
	 */
	function round($value, $currencyCode, $lang);
} 


class catalog_PriceFormatter
{
	/**
	 * @var catalog_PriceFormatter
	 */
	private static $instance;
	
	/**
	 * @var catalog_PriceFormatStrategy
	 */
	private $strategy = null;

	protected function __construct()
	{
		$className = Framework::getConfiguration('modules/catalog/priceFormatStrategyClass', false);
		if ($className !== false)
		{
			$this->strategy = new $className;
			if (!($this->strategy instanceof catalog_PriceFormatStrategy)
				// @deprecated (will be removed in 4.0) Second case for compatibility
				&& !($this->strategy instanceof catalog_PriceFormatter))
			{
				$this->strategy = null;
			}
		} 
	}
	
	/**
	 * @return catalog_PriceFormatter
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			$finalClassName = Injection::getFinalClassName(__CLASS__);
			self::$instance = new $finalClassName();
		}
		return self::$instance;
	}
	
	/**
	 * @return void
	 */
	public static function clearInstance()
	{
		self::$instance = null;
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
		if ($this->strategy !== null)
		{
			return $this->strategy->format($value, $currencyCode, $lang, $symbolPosition);
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
		if ($this->strategy !== null)
		{
			return $this->strategy->round($value, $currencyCode, $lang);
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