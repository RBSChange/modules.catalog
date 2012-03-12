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
		return DocumentHelper::getDocumentInstanceIfExists(intval($this->getProductId()));	
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
		return DocumentHelper::getDocumentInstance($this->getShopId());
	}
	
	/**
	 * @return catalog_persistentdocument_billingarea
	 */
	public function getBillingArea()
	{
		return DocumentHelper::getDocumentInstance($this->getBillingAreaId());
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocument | null
	 */
	public function getTarget()
	{
		return DocumentHelper::getDocumentInstanceIfExists(intval($this->getTargetId()));
	}
	
	/**
	 * @return String
	 */
	public function getTargetLabel()
	{
		$target = $this->getTarget();
		return ($target instanceof f_persistentdocument_PersistentDocument) ? $target->getLabel() : '-';
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument|integer $target
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
		$this->setValue(0.0);
		$this->setValueWithoutDiscount(null);
	}
	
	/**
	 * @return boolean
	 */
	public function isDiscount()
	{
		return ($this->getValueWithoutDiscount() !== null);
	}
	
	public function removeDiscount()
	{
		if ($this->isDiscount())
		{
			$this->setValue($this->getValueWithoutDiscount());
			$this->setValueWithoutDiscount(null);
		}
	}

	/**
	 * @param double $value
	 */
	public function setDiscountValue($value)
	{
		$this->setValueWithoutDiscount($this->getValue());
		$this->setValue($value);
	}

	private $taxDocumentId = false;
	private $taxRate = false;
	
	/**
	 * @param boolean $throwsException
	 * @return catalog_persistentdocument_tax
	 * @throws Exception
	 */
	public function getTax($throwsException = true)
	{
		if ($this->taxDocumentId === false)
		{
			$taxZone = catalog_TaxService::getInstance()->getCurrentTaxZone($this->getShop());
			$taxDocument = catalog_TaxService::getInstance()->getTaxDocumentByKey($this->getBillingAreaId(), $this->getTaxCategory(), $taxZone);
			if ($taxDocument === null)
			{
				$this->taxDocumentId = null;
				$this->taxRate = 0.0;
			}
			else
			{
				$this->taxDocumentId = $taxDocument->getId();
				$this->taxRate = $taxDocument->getRate();
				return $taxDocument;
			}
		}
		
		if ($this->taxDocumentId === null)
		{
			if ($throwsException)
			{
				throw new Exception('Tax document not found');
			}
			return null;
		}
		
		return DocumentHelper::getDocumentInstance($this->taxDocumentId);
	}
	
	/**
	 * @return double
	 */
	private function getTaxRate()
	{
		if ($this->taxRate === false) {$this->getTax(false);}
		return $this->taxRate;
	}
	
	/**
	 * @return double
	 */
	public function getValueWithTax()
	{
		if ($this->hasPricePart())
		{
			$value = 0.0;
			foreach ($this->getPricePartArray() as $price)
			{
				/* @var $price catalog_persistentdocument_price */
				$value += $price->getValueWithTax();
			}
			return $value;
		}
		
		if (!$this->getStoreWithTax())
		{
			return catalog_TaxService::getInstance()->addTaxByRate($this->getValue(), $this->getTaxRate());
		}
		return $this->getValue();
	}

	/**
	 * @return double
	 */
	public function getValueWithoutTax()
	{
		if ($this->hasPricePart())
		{
			$value = 0.0;
			foreach ($this->getPricePartArray() as $price)
			{
				/* @var $price catalog_persistentdocument_price */
				$value += $price->getValueWithoutTax();
			}
			return $value;
		}
	
		if ($this->getStoreWithTax())
		{
			return catalog_TaxService::getInstance()->removeTaxByRate($this->getValue(), $this->getTaxRate());
		}
		return $this->getValue();
	}
	
	/**
	 * @param double $valueWithoutTax
	 */
	public function setValueWithoutTax($valueWithoutTax)
	{
		if ($this->getStoreWithTax())
		{
			$this->setValue(catalog_TaxService::getInstance()->addTaxByRate($valueWithoutTax, $this->getTaxRate()));
		}
		else
		{
			$this->setValue($valueWithoutTax);
		}
	}
	
	/**
	 * @return double|null
	 */
	public function getOldValueWithTax()
	{
		if ($this->hasPricePart())
		{
			$value = null;
			foreach ($this->getPricePartArray() as $price)
			{
				/* @var $price catalog_persistentdocument_price */
				$v = $price->getOldValueWithTax();
				if ($v !== null)
				{
					$value = ($value === null) ? $v : $value + v;
				}
			}
			return $value;
		}
		
		if ($this->getValueWithoutDiscount() !== null)
		{
			if (!$this->getStoreWithTax())
			{
				return catalog_TaxService::getInstance()->addTaxByRate($this->getValueWithoutDiscount(), $this->getTaxRate());
			}
			return $this->getValueWithoutDiscount();
		}
		return null;
	}
	
	/**
	 * @return double|null
	 */
	public function getOldValueWithoutTax()
	{
		if ($this->hasPricePart())
		{
			$value = null;
			foreach ($this->getPricePartArray() as $price)
			{
				/* @var $price catalog_persistentdocument_price */
				$v = $price->getOldValueWithoutTax();
				if ($v !== null)
				{
					$value = ($value === null) ? $v : $value + $v;
				}
			}
			return $value;
		}
	
		if ($this->getValueWithoutDiscount() !== null)
		{
			if ($this->getStoreWithTax())
			{
				return catalog_TaxService::getInstance()->removeTaxByRate($this->getValueWithoutDiscount(), $this->getTaxRate());
			}
			return $this->getValueWithoutDiscount();
		}
		return null;
	}
	
	/**
	 * @param double|null $oldValueWithoutTax
	 */
	public function setOldValueWithoutTax($oldValueWithoutTax)
	{
	
		if ($oldValueWithoutTax!== null)
		{
			if ($this->getStoreWithTax())
			{
				$this->setValueWithoutDiscount(catalog_TaxService::getInstance()->addTaxByRate($oldValueWithoutTax, $this->getTaxRate()));
			}
			else
			{
				$this->setValueWithoutDiscount($oldValueWithoutTax);
			}
		}
		else
		{
			$this->setValueWithoutDiscount(null);
		}
	}
	
	
	/**
	 * @var catalog_persistentdocument_price[]
	 */
	private $priceParts;
	
	/**
	 * @return boolean
	 */
	public function hasPricePart()
	{
		return ($this->priceParts !== null && count($this->priceParts));
	}
	
	/**
	 * @return void
	 */
	public function removeAllPricePart()
	{
		$this->priceParts = null;
	}
	
	/**
	 * @var catalog_persistentdocument_price $price
	 * @return void
	 */
	public function addPricePart($price)
	{
		if ($this->priceParts === null)
		{
			$this->priceParts = array($price);
		}
		else
		{
			$this->priceParts[] = $price;
		}
	}	
	
	/**
	 * @return catalog_persistentdocument_price[]
	 */
	public function getPricePartArray()
	{
		if ($this->hasPricePart())
		{
			return $this->priceParts;
		}
		return array();
	}
	
	/**
	 * @var catalog_persistentdocument_price[] $priceArray
	 */
	public function setPricePartArray($priceArray)
	{
		$this->priceParts = null;
		if (is_array($priceArray) && count($priceArray))
		{
			foreach ($priceArray as $price)
			{
				$this->addPricePart($price);
			}
		}
	}	
	

	//Templating 
	
	/**
	 * @param double|null $value
	 * @return string
	 */
	public function formatValue($value)
	{
		$ba = $this->getBillingArea();
		return catalog_PriceFormatter::getInstance()->format($value, $ba->getCurrency()->getCode(), null, $ba->getCurrencyPosition());
	}

	/**
	 * @return string
	 */
	public function getCurrencyCode()
	{
		return $this->getBillingArea()->getCurrencyCode();
	}
	
	/**
	 * @return string
	 */
	public function getCurrencySymbol()
	{
		return $this->getBillingArea()->getCurrency()->getSymbol();
	}
	
	/**
	 * @return string
	 */
	public function getFormattedValueWithTax()
	{
		$ba = $this->getBillingArea();
		return catalog_PriceFormatter::getInstance()->format($this->getValueWithTax(), $ba->getCurrency()->getCode(), null, $ba->getCurrencyPosition());
	}
	
	/**
	 * @return string
	 */
	public function getFormattedValueWithoutTax()
	{
		$ba = $this->getBillingArea();
		return catalog_PriceFormatter::getInstance()->format($this->getValueWithoutTax(), $ba->getCurrency()->getCode(), null, $ba->getCurrencyPosition());
	}
	
	/**
	 * @return string
	 */
	public function getFormattedOldValueWithTax()
	{
		$ba = $this->getBillingArea();
		return catalog_PriceFormatter::getInstance()->format($this->getOldValueWithTax(), $ba->getCurrency()->getCode(), null, $ba->getCurrencyPosition());

	}
	
	/**
	 * @return string
	 */
	public function getFormattedOldValueWithoutTax()
	{
		$ba = $this->getBillingArea();
		return catalog_PriceFormatter::getInstance()->format($this->getOldValueWithoutTax(), $ba->getCurrency()->getCode(), null, $ba->getCurrencyPosition());
	}
	
	/**
	 * @return string
	 */
	public function getFormattedDiscountWithTax()
	{
		$v = $this->getOldValueWithTax();
		if ($v !== null) {$v -= $this->getValueWithTax();}
		$ba = $this->getBillingArea();
		return catalog_PriceFormatter::getInstance()->format($v, $ba->getCurrency()->getCode(), null, $ba->getCurrencyPosition());
	}
	
	/**
	 * @return string
	 */
	public function getFormattedDiscountWithoutTax()
	{
		$v = $this->getOldValueWithoutTax();
		if ($v !== null) {$v -= $this->getValueWithoutTax();}
		$ba = $this->getBillingArea();
		return catalog_PriceFormatter::getInstance()->format($v, $ba->getCurrency()->getCode(), null, $ba->getCurrencyPosition());
	}
	
	/**
	 * @return string
	 */
	public function getFormattedEcoTax()
	{
		$ba = $this->getBillingArea();
		return catalog_PriceFormatter::getInstance()->format($this->getEcoTax(), $ba->getCurrency()->getCode(), null, $ba->getCurrencyPosition());
	}
		
	//Back office editor
	
	/**
	 * @return string
	 */
	public function getBoValueJSON()
	{
		
		$shop = $this->getShop();
		$ba = $this->getBillingArea();
		$currencyDoc = $ba->getCurrency();
				
		$editTTC = $ba->getBoEditWithTax();
		$taxCategory = $this->getTaxCategory();
		$taxCategories = catalog_TaxService::getInstance()->getBoTaxeInfoForBillingArea($ba);		
		$v = $this->isDiscount() ? $this->getValueWithoutDiscount() : $this->getValue();
		
		$taxRate = $taxCategories[$taxCategory]['rate'];
		if ($this->getStoreWithTax())
		{
			$valueTTC = $v;
			$valueHT = catalog_PriceFormatter::getInstance()->round(catalog_TaxService::getInstance()->removeTaxByRate($valueTTC, $taxRate), $currencyDoc->getCode());
		}
		else
		{
			$valueHT = $v;
			$valueTTC = catalog_PriceFormatter::getInstance()->round(catalog_TaxService::getInstance()->addTaxByRate($valueHT, $taxRate), $currencyDoc->getCode());
		}
		
		$array = array('value' => $editTTC ? $valueTTC : $valueHT, 'editTTC' => $editTTC,  
			'valueTTC' => $valueTTC, 'valueHT' => $valueHT , 		
			'taxCategory' => $taxCategory, 
			'taxCategories' => $taxCategories, 
			'currency' => $currencyDoc->getSymbol(), 
			'currencyCode' => $currencyDoc->getCode());
		
		return JsonService::getInstance()->encode($array);
	}
	
	/**
	 * @param string $value
	 */
	public function setBoValueJSON($value)
	{
		$parts = explode(',', $value);
		if (count($parts) != 2 || $parts[0] == '' || $parts[1] == '')
		{
			$parts = array(0.0, '0');
		}
		
		$shop = $this->getShop();
		$ba = $this->getBillingArea();
		
		
		$this->setTaxCategory($parts[1]);
		$v = doubleval($parts[0]);
		if ($ba->getBoEditWithTax() != $this->getStoreWithTax())
		{
			$taxZone = $ba->getDefaultZone();
			$taxRate = catalog_TaxService::getInstance()->getTaxRateByKey($ba->getId(), $this->getTaxCategory(), $taxZone);
			if ($this->getStoreWithTax())
			{
				$v = catalog_TaxService::getInstance()->addTaxByRate($v, $taxRate);
			}
			else
			{
				$v = catalog_TaxService::getInstance()->removeTaxByRate($v, $taxRate);
			}
		}

		if ($this->isDiscount())
		{
			$this->setValueWithoutDiscount($v);
		}
		else
		{
			$this->setValue($v);
		}
	}
	
	/**
	 * @return string
	 */
	public function getBoDiscountValueJSON()
	{
		$shop = $this->getShop();
		$ba = $this->getBillingArea();
		$currencyDoc = $ba->getCurrency();
		
		$editTTC = $ba->getBoEditWithTax();
		$taxCategory = $this->getTaxCategory();
		$taxCategories = catalog_TaxService::getInstance()->getBoTaxeInfoForBillingArea($ba);
		$v = $this->isDiscount() ? $this->getValue() : null;
		$taxRate = $taxCategories[$taxCategory]['rate'];
		
		if ($v == null)
		{
			$valueHT = $v;
			$valueTTC = $valueHT;
		}
		elseif ($this->getStoreWithTax())
		{
			$valueTTC = $v;
			$valueHT = catalog_PriceFormatter::getInstance()->round(catalog_TaxService::getInstance()->removeTaxByRate($valueTTC, $taxRate), $currencyDoc->getCode());
		}
		else
		{
			$valueHT = $v;
			$valueTTC = catalog_PriceFormatter::getInstance()->round(catalog_TaxService::getInstance()->addTaxByRate($valueHT, $taxRate), $currencyDoc->getCode());
		}
		
		$array = array('value' => $editTTC ? $valueTTC : $valueHT, 'editTTC' => $editTTC,
			'valueTTC' => $valueTTC, 'valueHT' => $valueHT, 
			'taxCategory' => $taxCategory, 
			'taxCategories' => $taxCategories, 
			'hideCategory' => true, 
			'currency' => $currencyDoc->getSymbol(), 
			'currencyCode' => $currencyDoc->getCode());
		
		return JsonService::getInstance()->encode($array);
	}
	
	/**
	 * @param string $value for example 12.56,4
	 */
	public function setBoDiscountValueJSON($value)
	{
		$parts = explode(',', $value);
		$shop = $this->getShop();
		$ba = $this->getBillingArea();
		$v = (f_util_StringUtils::isEmpty($parts[0])) ? null : doubleval($parts[0]);
		if ($v === null)
		{
			$this->removeDiscount();
		}
		else
		{
			if ($ba->getBoEditWithTax() != $this->getStoreWithTax())
			{
				$taxZone = $ba->getDefaultZone();
				$taxRate = catalog_TaxService::getInstance()->getTaxRateByKey($ba->getId(), $this->getTaxCategory(), $taxZone);
				if ($this->getStoreWithTax())
				{
					$v = catalog_TaxService::getInstance()->addTaxByRate($v, $taxRate);
				}
				else
				{
					$v = catalog_TaxService::getInstance()->removeTaxByRate($v, $taxRate);
				}
			}
			
			if ($this->isDiscount())
			{
				$this->setValue($v);
			}
			else
			{
				$this->setDiscountValue($v);
			}		
		}		
	}
	
	//Scripting an webService
	
	public function setImportPriceValue($valueWithTax)
	{
		if (!$this->getStoreWithTax())
		{
			$ba = $this->getBillingArea();
			$taxZone = $ba->getDefaultZone();
			$taxRate = catalog_TaxService::getInstance()->getTaxRateByKey($ba->getId(), $this->getTaxCategory(), $taxZone);
			$v = catalog_TaxService::getInstance()->removeTaxByRate($valueWithTax, $taxRate);
		}
		else
		{
			$v = doubleval($valueWithTax);
		}

		$this->setValue($v);
	}
	
	public function setImportPriceOldValue($oldValueWithTax)
	{
		if (f_util_StringUtils::isEmpty($oldValueWithTax))
		{
			$v = null;
		}
		elseif (!$this->getStoreWithTax())
		{
			$ba = $this->getBillingArea();
			$taxZone = $ba->getDefaultZone();
			$taxRate = catalog_TaxService::getInstance()->getTaxRateByKey($ba->getId(), $this->getTaxCategory(), $taxZone);
			$v = catalog_TaxService::getInstance()->removeTaxByRate($oldValueWithTax, $taxRate);
		}
		else
		{
			$v = doubleval($oldValueWithTax);
		}
		$this->setValueWithoutDiscount($v);
	}
	
	//DEPRECATED
	
	/**
	 * @deprecated
	 */
	public function getTaxCode()
	{
		return $this->getTax()->getCode();
	}
}