<?php
/**
 * catalog_persistentdocument_price
 * @package modules.catalog
 */
class catalog_persistentdocument_price extends catalog_persistentdocument_pricebase
{
	/**
	 * @return catalog_persistentdocument_product
	 */
	public function getProduct()
	{
		return DocumentHelper::getDocumentInstance($this->getProductId());
	}
	
	/**
	 * @return catalog_persistentdocument_shop
	 */
	public function getShop()
	{
		return DocumentHelper::getDocumentInstance($this->getShopId());
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
	 * @return Boolean
	 */
	public function isDiscount()
	{
		return ($this->getOldValueWithTax() !== null);
	}
	
	/**
	 * @return Double
	 */
	public function getFormattedValueWithTax()
	{
		return $this->getDocumentService()->formatValue($this->getValueWithTax(), $this->getShop());
	}
	
	/**
	 * @return Double
	 */
	public function getFormattedValueWithoutTax()
	{
		return $this->getDocumentService()->formatValue($this->getValueWithoutTax(), $this->getShop());
	}
	
	/**
	 * @return Double
	 */
	public function getFormattedOldValueWithTax()
	{
		$value = parent::getOldValueWithTax();
		return ($value !== null) ? $this->getDocumentService()->formatValue($value, $this->getShop()) : null;
	}
	
	/**
	 * @return Double
	 */
	public function getFormattedOldValueWithoutTax()
	{
		$value = parent::getOldValueWithoutTax();
		return ($value !== null) ? $this->getDocumentService()->formatValue($value, $this->getShop()) : null;
	}
	
	/**
	 * @return Double
	 */
	public function getFormattedEcoTax()
	{
		$value = parent::getEcoTax();
		return ($value !== null) ? $this->getDocumentService()->formatValue($value, $this->getShop()) : null;
	}
	
	/**
	 * @param Double $value
	 */
	public function setValue($value)
	{
		if ($this->getOldValueWithTax() !== null)
		{
			$this->setPriceOldValue($value);
		}
		else
		{
			$this->setPriceValue($value);
		}
	}
	
	/**
	 * @param Double $value
	 */
	public function setBoValue($value)
	{
		$this->setValue($value);
	}
	
	/**
	 * @return Double
	 */
	public function getValue()
	{
		if ($this->getOldValueWithTax() !== null)
		{
			return $this->getPriceOldValue();
		}
		else
		{
			return $this->getPriceValue();
		}
	}
	
	/**
	 * @return Double
	 */
	public function getBoValue()
	{
		return $this->getBoValueArray($this->getValue());
	}
	
	/**
	 * @param Double $value
	 */
	public function setDiscountValue($value)
	{
		if ($this->getOldValueWithTax() === null)
		{
			$this->setOldValueWithTax($this->getValueWithTax());
			$this->setOldValueWithoutTax($this->getValueWithoutTax());
		}
		$this->setPriceValue($value);
	}
	
	/**
	 * @param Double $value
	 */
	public function setBoDiscountValue($value)
	{
		if ($value === null && $this->getOldValueWithTax() !== null)
		{
			$this->setValueWithTax($this->getOldValueWithTax());
			$this->setValueWithoutTax($this->getOldValueWithoutTax());
			$this->setOldValueWithTax(null);
			$this->setOldValueWithoutTax(null);
		}
		else 
		{
			$this->setDiscountValue($value);
		}
	}
	
	/**
	 * @return Double
	 */
	public function getDiscountValue()
	{
		if ($this->getOldValueWithTax() !== null)
		{
			return $this->getPriceValue();
		}
		else
		{
			return null;
		}
	}	
	
	/**
	 * @return Double
	 */
	public function getBoDiscountValue()
	{
		return $this->getBoValueArray($this->getDiscountValue());
	}	
	
	/**
	 * @return Double
	 */
	private function getBoValueArray($value)
	{
		$shop = $this->getShop();
		return JsonService::getInstance()->encode(array(
			'value' => $value, 
			'currency' => $shop->getCurrencySymbol(),
			'mode' => $shop->getPriceModeLabel()
		));
	}	

	/**
	 * @param Double $value
	 */
	public function setPriceOldValue($value)
	{
		if ($value === null)
		{
			$this->setOldValueWithTax(null);
			$this->setOldValueWithoutTax(null);
		}
		else
		{
			$shop = $this->getShop();
			$this->setOldValueWithTax(catalog_PriceHelper::getValueWithTax($value, $this->getTaxCode(), $shop));
			$this->setOldValueWithoutTax(catalog_PriceHelper::getValueWithoutTax($value, $this->getTaxCode(), $shop));
		}
	}
	
	/**
	 * @param Double $value
	 */
	public function setPriceValue($value)
	{
		if ($value === null)
		{
			$this->setValueWithTax(null);
			$this->setValueWithoutTax(null);
		}
		else
		{
			$shop = $this->getShop();
			$this->setValueWithTax(catalog_PriceHelper::getValueWithTax($value, $this->getTaxCode(), $shop));
			$this->setValueWithoutTax(catalog_PriceHelper::getValueWithoutTax($value, $this->getTaxCode(), $shop));
		}
	}
	
	/**
	 * @return Double
	 */
	private function getPriceOldValue($value)
	{
		if ($this->getOldValueWithTax() === null)
		{
			return null;
		}
		else
		{
			$shop = $this->getShop();
			if (catalog_PriceHelper::getPriceMode($shop) === catalog_PriceHelper::MODE_B_TO_C)
			{
				return $this->getOldValueWithTax();
			}
			else 
			{
				return $this->getOldValueWithoutTax();
			}
		}
	}
	
	/**
	 * @return Double
	 */
	private function getPriceValue()
	{
		if ($this->getValueWithTax() === null)
		{
			return null;
		}
		else
		{
			$shop = $this->getShop();
			if (catalog_PriceHelper::getPriceMode($shop) === catalog_PriceHelper::MODE_B_TO_C)
			{
				return $this->getValueWithTax();
			}
			else 
			{
				return $this->getValueWithoutTax();
			}
		}
	}
}