<?php
/**
 * catalog_persistentdocument_shippingfilter
 * @package catalog.persistentdocument
 */
class catalog_persistentdocument_shippingfilter extends catalog_persistentdocument_shippingfilterbase 
{
	
	public function getFormattedValueWithTax()
	{
		return $this->getShop()->formatPrice($this->getValueWithTax());
	}
}