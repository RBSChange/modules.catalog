<?php
/**
 * catalog_persistentdocument_declinedproduct
 * @package modules.catalog
 */
class catalog_persistentdocument_declinedproduct extends catalog_persistentdocument_declinedproductbase
{
	/**
	 * @return String
	 */
	public function getDetailBlockName()
	{
		return 'declinedproduct';
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return Boolean
	 */
	public function isAvailable($shop)
	{
		$defaultDeclination = $this->getDefaultDeclination($shop);
		return ($defaultDeclination !== null) ? $defaultDeclination->isAvailable($shop) : false;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return String
	 */
	public function getAvailability($shop = null)
	{
		if ($shop === null)
		{
			$shop = catalog_ShopService::getInstance()->getCurrentShop();
		}
		$defaultDeclination = $this->getDefaultDeclination($shop);
		if ($defaultDeclination !== null)
		{
			return $defaultDeclination->getAvailability($shop);
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param customer_persistentdocument_customer $customer
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getPrice($shop, $customer, $quantity = 1)
	{
		$defaultDeclination = $this->getDefaultDeclination($shop);
		return ($defaultDeclination !== null) ? catalog_PriceService::getInstance()->getPrice($defaultDeclination, $shop, $customer, $quantity) : null;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param customer_persistentdocument_customer $customer
	 * @return catalog_persistentdocument_price[]
	 */
	public function getPrices($shop, $customer)
	{
		$defaultDeclination = $this->getDefaultDeclination($shop);
		return ($defaultDeclination !== null) ? catalog_PriceService::getInstance()->getPrices($defaultDeclination, $shop, $customer) : false;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_productdeclination
	 */
	public function getDefaultDeclination($shop)
	{
		return $this->getDocumentService()->getDefaultDeclination($this, $shop);
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_productdeclination[]
	 */
	public function getDisplayableDeclinations($shop)
	{
		return $this->getDocumentService()->getDisplayableDeclinations($this, $shop);
	}
	
	// Methods required for creation form.
	
	/**
	 * @param String $value
	 */
	public function setDeclinationLabel0($value)
	{
		$this->getOrCreateDeclination(0)->setLabel($value);
	}
		
	/**
	 * @param media_persistentdocument_media $value
	 */
	public function setDeclinationVisual0($value)
	{
		$value = ($value === null) ? null : DocumentHelper::getDocumentInstance($value);
		$this->getOrCreateDeclination(0)->setVisual($value);
	}
	
	/**
	 * @param String $value
	 */
	public function setDeclinationCodeReference0($value)
	{
		$this->getOrCreateDeclination(0)->setCodeReference($value);
	}
		
	/**
	 * @param Integer $index
	 * @return catalog_persistentdocument_productdeclination
	 */
	private function getOrCreateDeclination($index)
	{
		if ($this->getDeclinationCount() <= $index)
		{
			$this->setDeclination($index, catalog_ProductdeclinationService::getInstance()->getNewDocumentInstance());
		}
		return $this->getDeclination($index);
	}
}