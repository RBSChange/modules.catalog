<?php
/**
 * catalog_GaugeObject
 * @package modules.catalog
 */
class catalog_GaugeObject
{
	private $gauge = 0;
	
	private static $imageBaseName;
	private static $imageExtension;
	private static $maxScore;
	
	/**
	 * @param Double $value
	 */
	public function __construct($value)
	{
		if (self::$imageBaseName == null)
		{
			try
			{
				$delegateClassName = Framework::getConfiguration('modules/catalog/gaugeDelegate');
				self::setDelegate($delegateClassName);
			} 
			catch (ConfigurationException $e)
			{
				// Nothing to do here
			}
			
			if (self::$imageBaseName == null)
			{
				self::$imageBaseName = 'stock-gauge-';
			}
			
			if (self::$imageExtension == null)
			{
				self::$imageExtension = 'png';
			}
			
			if (self::$maxScore == null)
			{
				self::$maxScore = 5;
			}
		}
		$this->gauge = round(self::$maxScore * $value);
	}
	
	/**
	 * @param Integer $value
	 */
	public function setGauge($value)
	{
		$this->gauge = $value;
	}
	
	/**
	 * @param String $className
	 */
	public static function setDelegate($className)
	{
		$classInstance = new $className();
		$reflectionClass = new ReflectionClass($className);
		
		if ($reflectionClass->hasMethod("getGaugeImageBaseName"))
		{
			self::$imageBaseName = $classInstance->getGaugeImageBaseName();
		}
		
		if ($reflectionClass->hasMethod("getGaugeImageExtension"))
		{
			self::$imageExtension = $classInstance->getGaugeImageExtension();
		}
		
		if ($reflectionClass->hasMethod("getGaugeMaxScore"))
		{
			self::$maxScore = $classInstance->getGaugeMaxScore();
		}
	}
	
	/**
	 * @return String
	 */
	public function getImageUrl()
	{
		return htmlentities(MediaHelper::getFrontofficeStaticUrl(self::$imageBaseName . strval($this->gauge)) . '.' . self::$imageExtension);
	}
	
	/**
	 * @return String
	 */
	public function getAltText()
	{
		return f_Locale::translate('&modules.catalog.frontoffice.Stock-gauge-' . strval($this->gauge) . ';');
	}
	
	/**
	 * @return String
	 */
	public function getClassName()
	{
		return 'stock-gauge-' . strval($this->gauge);
	}
}