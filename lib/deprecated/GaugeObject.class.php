<?php
/**
 * @deprecated use change:gauge
 */
class catalog_GaugeObject
{
	private $gauge = 0;
	
	private static $imageBaseName;
	private static $imageExtension;
	private static $maxScore;
	
	/**
	 * @deprecated
	 */
	public function __construct($value)
	{
		if (self::$imageBaseName == null)
		{
			$delegateClassName = Framework::getConfiguration('modules/catalog/gaugeDelegate', false);
			if ($delegateClassName)
			{
				self::setDelegate($delegateClassName);
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
	 * @deprecated
	 */
	public function setGauge($value)
	{
		$this->gauge = $value;
	}
	
	/**
	 * @deprecated
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
	 * @deprecated
	 */
	public function getImageUrl()
	{
		return htmlentities(MediaHelper::getFrontofficeStaticUrl(self::$imageBaseName . strval($this->gauge)) . '.' . self::$imageExtension);
	}
	
	/**
	 * @deprecated
	 */
	public function getAltText()
	{
		return LocaleService::getInstance()->trans('m.catalog.frontoffice.stock-gauge-' . strval($this->gauge), array('ucf'));
	}
	
	/**
	 * @deprecated
	 */
	public function getClassName()
	{
		return 'stock-gauge-' . strval($this->gauge);
	}
}