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
	
	private $defaultDeclinations = array();
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_productdeclination
	 */
	public function getDefaultDeclination($shop)
	{
		$key = 's' . $shop->getId();
		if (!array_key_exists($key, $this->defaultDeclinations))
		{
			$this->defaultDeclinations[$key] = $this->getDocumentService()->getDefaultDeclination($this, $shop);
		}
		return $this->defaultDeclinations[$key];
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
	 * @param Integer $value
	 */
	public function setDeclinationStockQuantity0($value)
	{
		$this->getOrCreateDeclination(0)->setStockQuantity($value);
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
	
	private $declinationToDelete;
	
	/**
	 * @param catalog_persistentdocument_productdeclination[] $array
	 */
	public final function setDeclinationsToDelete($array)
	{
		$this->declinationToDelete = $array;
	}
	
	/**
	 * @return catalog_persistentdocument_productdeclination[]
	 */
	public final function getDeclinationsToDelete()
	{
		return $this->declinationToDelete;
	}
	
	private $synchronizePricesFrom;
	
	/**
	 * @return integer
	 */
	public function getSynchronizePricesFrom()
	{
		return $this->synchronizePricesFrom;
	}
	
	/**
	 * @param integer $declinationId
	 */
	public function setSynchronizePricesFrom($declinationId)
	{
		$declinationId = intval($declinationId);
		if ($declinationId > 0)
		{
			$this->synchronizePricesFrom = $declinationId;
			$this->setSynchronizePrices(true);
		}
		else
		{
			$this->synchronizePricesFrom = null;
			$this->setSynchronizePrices(false);
		}
	}
	
	/**
	 * @param integer $declinationId
	 */
	public function clearSynchronizePricesFrom()
	{
		$this->synchronizePricesFrom = null;
	}
	
	/**
	 * @param string $actionType
	 * @param array $formProperties
	 */
	public function addFormProperties($propertiesNames, &$formProperties)
	{	
		$formProperties['synchronizePrices'] = $this->getSynchronizePrices();
	}
	
	/**
	 * @return boolean
	 */
	public function isPricePanelEnabled()
	{
		return $this->getSynchronizePrices();
	}
	
	/**
	 * @return string
	 */
	public function getPricePanelDisabledMessage()
	{
		if (!$this->isPricePanelEnabled())
		{
			return f_Locale::translate('&modules.catalog.bo.doceditor.panel.prices.disabled.Declinedproduct-not-synchronized;');
		}
		return null;
	}
}