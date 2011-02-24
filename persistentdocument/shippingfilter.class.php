<?php
/**
 * catalog_persistentdocument_shippingfilter
 * @package catalog.persistentdocument
 */
class catalog_persistentdocument_shippingfilter extends catalog_persistentdocument_shippingfilterbase 
{

	private $taxDocumentId = null;
	
	/**
	 * @return catalog_persistentdocument_tax
	 * @throws Exception
	 */
	public function getTax()
	{
		if ($this->taxDocumentId === null)
		{
			$shop = $this->getShop();
			$taxZone = catalog_TaxService::getInstance()->getCurrentTaxZone($shop);
			$taxDocument = catalog_TaxService::getInstance()->getTaxDocument($shop->getId(), $this->getTaxCategory(), $taxZone);
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
	public function getValueWithTax()
	{
		if ($this->getValueWithoutTax())
		{
			return $this->getValueWithoutTax() * (1 + $this->getTaxRate());
		}
		return $this->getValueWithoutTax();
	}
	
	/**
	 * @return string
	 */
	public function getTaxCode()
	{
		return  $this->getTax()->getCode();
	}
	
	public function getFormattedValueWithTax()
	{
		return $this->getShop()->formatPrice($this->getValueWithTax());
	}
	
	//Back office editor
	/**
	 * @return string
	 */
	public function getBoValueJSON()
	{
		$valueHT = $this->getValueWithoutTax();
		
		$shop = $this->getShop();
		$taxZone = $shop->getBoTaxZone();
		$currencyDoc = catalog_CurrencyService::getInstance()->getByCode($shop->getCurrencyCode());
		
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
		$this->setValueWithoutTax($valueHT);
	}

	/**
	 * 
	 * @param order_CartInfo $cartInfo
	 * @return shipping_persistentdocument_mode
	 */
	public function evaluateValue($cartInfo)
	{
		$fees = $this->getFees();
		if ($fees !== null)
		{
			$fees->getDocumentService()->simulateShippingFilter($fees, $this, $cartInfo);
		}	
	}
	
	/**
	 * @param order_persistentdocument_fees $fees
	 */
	public function setFees($fees)
	{
		if ($fees instanceof order_persistentdocument_fees) 
		{
			$this->setFeesId($fees->getId());
		}
		else
		{
			$this->setFeesId(null);
		}
	}
	
	/**
	 * @return order_persistentdocument_fees
	 */
	public function getFees()
	{
		if ($this->getFeesId())
		{
			return order_persistentdocument_fees::getInstanceById($this->getFeesId());
		}
		return null;
	}	
}