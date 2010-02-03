<?php
/**
 * catalog_DiscountHelper
 * @package modules.catalog
 */
class catalog_DiscountHelper
{
	const TYPE_PRICE = 'price';
	const TYPE_PERCENTAGE = 'percentage';
	const TYPE_VALUE = 'value';
	const DISCOUNT_TYPE_LIST_ID = 'modules_catalog/discounttypes';
	const DISCOUNT_TYPE_SHORT_LIST_ID = 'modules_catalog/discounttypesshort';

	/**
	 * @param Double $priceValue
	 * @param Double $discountValue
	 * @param String $discountType
	 * @return Double
	 */
	public function applyDiscountOnPrice($priceValue, $discountValue, $discountType)
	{
		$value = $priceValue;
		switch ($discountType)
		{
			case self::TYPE_PRICE :
				$value = $discountValue;
				break;

			case self::TYPE_PERCENTAGE :
				$value *= 1.0 - ($discountValue / 100.0);
				break;

			case self::TYPE_VALUE :
				$value -= $discountValue;
				break;
		}
		return $value;
	}
	
	/**
	 * @param Double $discountValue
	 * @param String $discountType
	 * @param String $priceFormat
	 * @param String $percentageFormat
	 * @retunr String
	 */
	public function formatDiscountValue($discountValue, $discountType, $priceFormat, $percentageFormat = '%s%%')
	{
		switch ($discountType)
		{
			case self::TYPE_PRICE :
				$formattedValue = catalog_PriceHelper::applyFormat($discountValue, $priceFormat);
				break;

			case self::TYPE_PERCENTAGE :
				$formattedValue = sprintf($percentageFormat, $discountValue);
				break;

			case self::TYPE_VALUE :
				$value = 0 - $discountValue;
				$formattedValue = catalog_PriceHelper::applyFormat($value, $priceFormat);
				break;
		}
		return $formattedValue;
	}
}