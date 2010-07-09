<?php
/**
 * catalog_QuantityHelper
 * @package modules.catalog
 */
class catalog_QuantityHelper
{
	const WEIGHT_TYPE_LIST_ID = 'modules_catalog/weightunits';
	const WEIGHT_TYPE_MG = 'mg';
	const WEIGHT_TYPE_G = 'g';
	const WEIGHT_TYPE_KG = 'kg';

	/**
	 * @param Double $value
	 * @param String $oldUnit
	 * @param String $newUnit
	 * @return Double
	 */
	public static function convertWeight($value, $oldUnit, $newUnit)
	{
		// If old unit equals the new one, just retrun the value.
		if ($oldUnit == $newUnit)
		{
			return $value;
		}
		
		// Conversion.
		$exposant = self::getWeightExposant($oldUnit, $newUnit);
		if (is_null($exposant))
		{
			return null;
		}
		return $value * pow(10, $exposant);
	}
	
	/**
	 * Each sub-array of $weights parameter must contain two keys : 'value' and 'unit'. 
	 * @param Array<Array<String, Mixed>> $weights
	 * @param Double $finalUnit
	 */
	public static function getSumOfWeights($weights, $finalUnit)
	{
		$result = 0;
		foreach ($weights as $weight)
		{
			$valueToAdd = self::convertWeight($weight['value'], $weight['unit'], $finalUnit);
			if (!is_null($valueToAdd))
			{
				$result += $valueToAdd;
			} 
		}
		return $result;
	}
	
	/**
	 * @param Double $weight
	 * @param String $weightUnit
	 * @return String
	 */
	public static function formatWeight($weight, $weightUnit)
	{
		if ($weight <= 1)
		{
			return f_Locale::translate('&modules.catalog.frontoffice.weight-unit-'.$weightUnit.'-singular;', array('weight' => $weight));
		}
		else 
		{
			return f_Locale::translate('&modules.catalog.frontoffice.weight-unit-'.$weightUnit.'-plural;', array('weight' => $weight));
		}
	}
	
	/**
	 * @param Double $price
	 * @param Double $weight
	 * @param String $weightUnit
	 * @param String $referenceWeightUnit
	 * @return Double
	 */
	public static function getPricePerWeightUnit($price, $weight, $weightUnit, $referenceWeightUnit = null)
	{
		// If no given reference weight unit, get the default one.
		if (is_null($referenceWeightUnit))
		{
			$referenceWeightUnit = self::getReferenceWeightUnit();
		}
		
		// Get the exposant, according to weight unit
		$exp1 = self::getWeightExposant($weightUnit, self::WEIGHT_TYPE_KG);
		if (is_null($exp1))
		{
			return null;
		}
		
		// Get the exposant, according to the reference weight unit.
		$exp2 = self::getWeightExposant($referenceWeightUnit, self::WEIGHT_TYPE_KG);
		if (is_null($exp2))
		{
			return null;
		}
		
		// Get the final exposant.
		$exp = $exp2 - $exp1;
		
		// Calculate the price.
		return ($price/$weight) * pow(10, $exp);
	}
	
	/**
	 * @var String
	 */
	private static $referenceWeightUnit;
	
	/**
	 * @param String $value
	 */
	public static function setReferenceWeightUnit($value)
	{
		self::$referenceWeightUnit = $value;
	}
	
	/**
	 * Available units :
	 *  - mg : milligram (self::WEIGHT_TYPE_MG)
	 *  - g : gram (self::WEIGHT_TYPE_G)
	 *  - kg : kilogram (self::WEIGHT_TYPE_KG)
	 * 
	 * @return String
	 */
	public static function getReferenceWeightUnit()
	{
		if (is_null(self::$referenceWeightUnit))
		{
			self::$referenceWeightUnit = Framework::getConfiguration('modules/catalog/referenceWeightUnit', false);
			if (self::$referenceWeightUnit === false)
			{
				self::$referenceWeightUnit = self::WEIGHT_TYPE_KG;
			}
		}
		return self::$referenceWeightUnit;
	}
	
	/**
	 * @param String $oldUnit
	 * @param String $newUnit
	 * @return Double
	 */
	private function getWeightExposant($oldUnit, $newUnit)
	{
		// Exposant to grams.
		$exp1 = self::getWeightExposantToGrams($oldUnit);
		if (is_null($exp1))
		{
			return null;
		}
		
		// Exposant to new unit.
		$exp2 = self::getWeightExposantToGrams($newUnit);
		if (is_null($exp2))
		{
			return null;
		}
		
		return $exp1 - $exp2;
	}
	
	/**
	 * @param String $unit
	 * @return Double
	 */
	private function getWeightExposantToGrams($unit)
	{
		// Exposant to grams.
		switch ($unit)
		{
			case self::WEIGHT_TYPE_MG : 
				$exp = -3;
				break;
				
			case self::WEIGHT_TYPE_G : 
				$exp = 0;
				break;
				
			case self::WEIGHT_TYPE_KG : 
				$exp = 3;
				break;
			
			default :
				$exp = null;
				break;
		}
		return $exp;
	}
}