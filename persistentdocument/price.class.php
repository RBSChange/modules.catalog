<?php
/**
 * catalog_persistentdocument_price
 * @package modules.catalog
 */
class catalog_persistentdocument_price extends catalog_persistentdocument_pricebase
{
	/**
	 * @return f_persistentdocument_PersistentDocument | null
	 */	
	public function getAttachedDocument()
	{
		if (intval($this->getProductId()) > 0)
		{
			try 
			{
				return DocumentHelper::getDocumentInstance($this->getProductId());
			}
			catch (Exception $e)
			{
				Framework::exception($e);
			}
		}
		return null;		
	}
	
	
	/**
	 * @return catalog_persistentdocument_product | null
	 */
	public function getProduct()
	{
		$product = $this->getAttachedDocument();
		if ($product instanceof catalog_persistentdocument_product)
		{
			return $product;
		}
		return null;
	}
	
	/**
	 * @return catalog_persistentdocument_shop
	 */
	public function getShop()
	{
		return catalog_persistentdocument_shop::getInstanceById($this->getShopId());
	}
	
	/**
	 * @return f_peristentoducument_PersistentDocument
	 */
	public function getTarget()
	{
		$targetId = $this->getTargetId();
		return ($targetId > 0) ? DocumentHelper::getDocumentInstance($targetId) : null;
	}
	
	/**
	 * @return String
	 */
	public function getTargetLabel()
	{
		$targetId = $this->getTargetId();
		return ($targetId > 0) ? DocumentHelper::getDocumentInstance($targetId)->getLabel() : '-';
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $target
	 */
	public function setTarget($target)
	{
		$targetId = 0;
		if ($target instanceof f_persistentdocument_PersistentDocument)
		{
			$targetId = $target->getId();
		}
		else if (intval($target) > 0)
		{
			$targetId = $target;
		}
		$this->setTargetId($targetId);
	}
	
	/**
	 * Set all values to zero.
	 */
	public function setToZero()
	{
		$this->setValueWithoutTax(0.0);
		$this->setOldValueWithoutTax(null);
	}
	
	/**
	 * @return boolean
	 */
	public function isDiscount()
	{
		return ($this->getOldValueWithoutTax() !== null);
	}
	
	public function removeDiscount()
	{
		$this->setValueWithoutTax($this->getOldValueWithoutTax());
		$this->setOldValueWithoutTax(null);
	}

	/**
	 * @param double $value
	 */
	public function setDiscountValue($value)
	{
		$this->setOldValueWithoutTax($this->getValueWithoutTax());
		$this->setValueWithoutTax($value);
	}

	private $taxDocumentId = null;
	
	
	/**
	 * @return catalog_persistentdocument_tax
	 * @throws Exception
	 */
	public function getTax()
	{
		if ($this->taxDocumentId === null)
		{
			$taxZone = catalog_TaxService::getInstance()->getCurrentTaxZone($this->getShop());
			$taxDocument = catalog_TaxService::getInstance()->getTaxDocument($this->getShopId(), $this->getTaxCategory(), $taxZone);
			if ($taxDocument === null)
			{
				throw new Exception('Tax document not found');
			}
			$this->taxDocumentId = $taxDocument->getId();
			return $taxDocument;
		}
		return catalog_persistentdocument_tax::getInstanceById($this->taxDocumentId);
	}
	
	/**
	 * @return double
	 * @throws Exception
	 */
	private function getTaxRate()
	{
		return $this->getTax()->getRate();
	}
	
	/**
	 * @return double
	 * @throws Exception
	 */
	public function getOldValueWithTax()
	{
		if ($this->getOldValueWithoutTax())
		{
			return $this->getOldValueWithoutTax() * (1 + $this->getTaxRate());
		}
		return $this->getOldValueWithoutTax();
	}
	
	/**
	 * @return double
	 * @throws Exception
	 */	
	public function getValueWithTax()
	{
		if ($this->getValueWithoutTax())
		{
			
			return $this->getValueWithoutTax() * (1 + $this->getTaxRate());
		}
		return $this->getValueWithoutTax();
	}

	//Templating 
	/**
	 * @return string
	 */
	public function getFormattedValueWithTax()
	{
		return catalog_PriceFormatter::getInstance()->format($this->getValueWithTax(), 
			catalog_CurrencyService::getInstance()->getCodeById($this->getCurrencyId()));
	}
	
	/**
	 * @return string
	 */
	public function getFormattedValueWithoutTax()
	{
		return catalog_PriceFormatter::getInstance()->format($this->getValueWithoutTax(), 
			catalog_CurrencyService::getInstance()->getCodeById($this->getCurrencyId()));
	}
	
	/**
	 * @return string
	 */
	public function getFormattedOldValueWithTax()
	{
		return catalog_PriceFormatter::getInstance()->format($this->getOldValueWithTax(), 
			catalog_CurrencyService::getInstance()->getCodeById($this->getCurrencyId()));
	}
	
	/**
	 * @return string
	 */
	public function getFormattedOldValueWithoutTax()
	{
		return catalog_PriceFormatter::getInstance()->format($this->getOldValueWithoutTax(), 
			catalog_CurrencyService::getInstance()->getCodeById($this->getCurrencyId()));
	}
	
	/**
	 * @return string
	 */
	public function getFormattedEcoTax()
	{
		return catalog_PriceFormatter::getInstance()->format($this->getEcoTax(), 
			catalog_CurrencyService::getInstance()->getCodeById($this->getCurrencyId()));
	}
	
	/**
	 * @return string
	 */
	public function getTaxCode()
	{
		return  $this->getTax()->getCode();
	}
	
	//Back office editor
	/**
	 * @return string
	 */
	public function getBoValueJSON()
	{
		$valueHT = $this->isDiscount() ? $this->getOldValueWithoutTax() : $this->getValueWithoutTax();
		
		$shop = $this->getShop();
		$taxZone = $shop->getBoTaxZone();
		if ($this->getCurrencyId())
		{
			$currencyDoc = catalog_persistentdocument_currency::getInstanceById($this->getCurrencyId());
		}
		else
		{
			$currencyDoc = catalog_CurrencyService::getInstance()->getByCode($shop->getCurrencyCode());
		}
		
		$editTTC = $taxZone !== null;
		$taxCategory = $this->getTaxCategory();
		$taxCategories = catalog_TaxService::getInstance()->getBoTaxeInfoForShop($this->getShop());
		if ($taxZone !== null && $valueHT > 0)
		{
			$valueTTC = catalog_PriceFormatter::getInstance()->round($valueHT * (1 + $taxCategories[$taxCategory]['rate']), $currencyDoc->getCode());
		}
		else
		{
			$valueTTC = $valueHT;
		}
		
		$array = array('value' => $editTTC ? $valueTTC : $valueHT, 'valueTTC' => $valueTTC, 'valueHT' => $valueHT , 'editTTC' => $editTTC, 'taxCategory' => $taxCategory, 
			'taxCategories' => $taxCategories, 'currency' => $currencyDoc->getSymbol(), 'currencyCode' => $currencyDoc->getCode());
		
		return JsonService::getInstance()->encode($array);
	}
	
	/**
	 * @example 12.56,4
	 * @param string $value
	 */
	public function setBoValueJSON($value)
	{
		Framework::info(__METHOD__ . "($value)");
		$parts = explode(',', $value);
		if (count($parts) != 2 || $parts[0] == '' || $parts[1] == '')
		{
			$this->setTaxCategory(null);
			return;
		}
		$this->setTaxCategory($parts[1]);
		$shop = $this->getShop();
		$taxZone = $shop->getBoTaxZone();
		if ($taxZone === null)
		{
			$valueHT = doubleval($parts[0]);
		}
		else
		{
			$rate = catalog_TaxService::getInstance()->getTaxRate($shop->getId(), $this->getTaxCategory(), $taxZone);
			$valueHT = doubleval($parts[0]) / (1 + $rate);
		}
		if ($this->isDiscount())
		{
			$this->setOldValueWithoutTax($valueHT);
		}
		else
		{
			$this->setValueWithoutTax($valueHT);
		}
	}
	
	/**
	 * @return string
	 */
	public function getBoDiscountValueJSON()
	{
		$valueHT = $this->isDiscount() ? $this->getValueWithoutTax() : null;
		$shop = $this->getShop();
		$taxZone = $shop->getBoTaxZone();
		if ($this->getCurrencyId())
		{
			$currencyDoc = catalog_persistentdocument_currency::getInstanceById($this->getCurrencyId());
		}
		else
		{
			$currencyDoc = catalog_CurrencyService::getInstance()->getByCode($shop->getCurrencyCode());
		}
		
		$editTTC = $taxZone !== null;
		$taxCategory = $this->getTaxCategory();
		$taxCategories = catalog_TaxService::getInstance()->getBoTaxeInfoForShop($this->getShop());
		if ($taxZone !== null && $valueHT > 0)
		{
			$valueTTC =  catalog_PriceFormatter::getInstance()->round($valueHT * (1 + $taxCategories[$taxCategory]['rate']), $currencyDoc->getCode());
		}
		else
		{
			$valueTTC = $valueHT;
		}
		
		$array = array('value' => $editTTC ? $valueTTC : $valueHT, 'valueTTC' => $valueTTC, 'valueHT' => $valueHT , 
			'editTTC' => $editTTC, 'taxCategory' => $taxCategory, 
			'taxCategories' => $taxCategories, 
			'hideCategory' => true, 
			'currency' => $currencyDoc->getSymbol(), 
			'currencyCode' => $currencyDoc->getCode());
		
		return JsonService::getInstance()->encode($array);
	}
	
	/**
	 * @example 12.56,4
	 * @param string $value
	 */
	public function setBoDiscountValueJSON($value)
	{
		Framework::info(__METHOD__ . "($value)");
		$parts = explode(',', $value);
		$shop = $this->getShop();
		$valueToSet = $parts[0] === '' ? null :  doubleval($parts[0]);
		$taxZone = $shop->getBoTaxZone();		
		if ($taxZone !== null && $valueToSet > 0)
		{
			$rate = catalog_TaxService::getInstance()->getTaxRate($shop->getId(), $this->getTaxCategory(), $taxZone);
			$valueToSet = doubleval($parts[0]) / (1 + $rate);
		}
		if ($valueToSet === null)
		{
			if ($this->isDiscount())
			{
				$this->removeDiscount();
			}
		}
		else if ($this->isDiscount())
		{
			$this->setValueWithoutTax($valueToSet);
		}
		else
		{
			$this->setDiscountValue($valueToSet);
		}
	}
	
	//Scripting an webService
	
	public function setImportPriceValue($value)
	{
		$shop = $this->getShop();
		$taxZone = $shop->getDefaultTaxZone();
		$rate = catalog_TaxService::getInstance()->getTaxRate($shop->getId(), $this->getTaxCategory(), $taxZone);
		$valueToSet = doubleval($value) / (1 + $rate);
		$this->setValueWithoutTax($valueToSet);
	}
	
	public function setImportPriceOldValue($value)
	{
		$shop = $this->getShop();
		$taxZone = $shop->getDefaultTaxZone();
		$rate = catalog_TaxService::getInstance()->getTaxRate($shop->getId(), $this->getTaxCategory(), $taxZone);
		$valueToSet = doubleval($value) / (1 + $rate);
		$this->setOldValueWithoutTax($valueToSet);
	}
}