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
			$billingArea = $this->getBillingArea();
			if ($billingArea === null)
			{
				$billingArea = $shop->getDefaultBillingArea();
			}
			$taxZone = catalog_TaxService::getInstance()->getCurrentTaxZone($shop);
			$taxDocument = catalog_TaxService::getInstance()->getTaxDocumentByKey($billingArea->getId(), $this->getTaxCategory(), $taxZone);
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
	public function getFormattedValueWithTax()
	{
		return $this->getShop()->getCurrentBillingArea()->formatPrice($this->getValueWithTax());
	}
	
	//Back office editor

	/**
	 * @return string
	 */
	public function getBillingAreaColumnLabel()
	{
		$ba = $this->getBillingArea();
		return $ba ? $ba->getTreeNodeLabel() : '-';
	}
	
	/**
	 * @return string
	 */
	public function getBoValueJSON()
	{
		$array = catalog_BillingareaService::getInstance()->buildBoPriceEditInfos($this->getValueWithoutTax(), $this->getShop(), $this->getTaxCategory(), $this->getBillingArea());
		return JsonService::getInstance()->encode($array);
	}
	
	/**
	 * @param string $value for example 12.56,4
	 */
	public function setBoValueJSON($value)
	{
		list($valueWithoutTax, $taxCategory) = catalog_BillingareaService::getInstance()->parseBoPriceEditInfos($value, $this->getShop(), $this->getBillingArea());
		$this->setTaxCategory($taxCategory);
		$this->setValueWithoutTax($valueWithoutTax);
	}

	/**
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