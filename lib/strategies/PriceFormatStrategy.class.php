<?php

interface catalog_PriceFormatStrategy
{
	/**
	 * Formats the value as a price using the currency identified by currencyCode in the given lang
	 * 
	 * @param Double $value
	 * @param String $currencyCode
	 * @param String $lang
	 */
	function format($value, $currencyCode, $lang = null);
	
	/**
	 * @param Double $value
	 * @param String $currencyCode
	 * @param String $lang
	 */
	function round($value, $currencyCode = null, $lang = null);
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


	private function __construct()
	{
		$className = Framework::getConfiguration('modules/catalog/priceFormatStrategyClass', false);
		if ($className !== false)
		{
			$this->strategy = new $className;
			if (!($this->strategy instanceof catalog_PriceFormatter))
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
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * @param double $value
	 * @param string $currencyCode
	 * @param string $lang
	 * @return string
	 */
	function format($value, $currencyCode, $lang = null)
	{
		if ($lang === null)
		{
			$lang = RequestContext::getInstance()->getLang();
		}
		if ($this->strategy !== null)
		{
			return $this->strategy->format($value, $currencyCode, $lang);
		}
		return $this->formatDefault($value, $currencyCode, $lang);
	}	
	
	/**
	 * @param double $value
	 * @param string $currencyCode
	 * @param string $lang
	 * @return double
	 */
	function round($value, $currencyCode = null, $lang = null)
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
	 * @return void
	 */
	public static final function clearInstance()
	{
		self::$instance = null;
	}
	
	/**
	 * @param double $value
	 * @param string $currencyCode
	 * @param string $lang
	 * @return string
	 */
	protected function formatDefault($value, $currencyCode, $lang)
	{
		if ($value === null || $value === '')
		{
			return '';
		}
		$currencySymbol = catalog_PriceHelper::getCurrencySymbol($currencyCode);
		$value = $this->roundDefault($value, $currencyCode, $lang);
		switch ($lang)
		{
			case 'de':
			case 'en':
				return sprintf($currencySymbol ." %s", number_format($value, 2, ',', ' '));
			case 'fr':
			default:
				return sprintf("%s ". $currencySymbol, number_format($value, 2, ',', ' '));
		}
	}
	
	/**
	 * @param double $value
	 * @param string $currencyCode
	 * @param string $lang
	 * @return double
	 */
	protected function roundDefault($value, $currencyCode, $lang)
	{
		return ($currencyCode === 'JPY') ? round($value, 0) :  round($value, 2);
	}
}