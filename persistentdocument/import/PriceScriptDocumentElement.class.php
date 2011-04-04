<?php
class catalog_PriceScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return catalog_persistentdocument_price
	 */
	protected function initPersistentDocument()
	{
		$price = $this->getDocumentService()->getNewDocumentInstance();

		if (isset($this->attributes['shop-refid']))
		{
			$price->setShopId($this->getComputedAttribute('shop')->getId());
			unset($this->attributes['shop-refid']);
		}
		 
		// Tax code must be set before setting values.
		if (isset($this->attributes['taxCode']))
		{
			$price->setTaxCategory($this->attributes['taxCode']);
			unset($this->attributes['taxCode']);
		}

		if (isset($this->attributes['value']))
		{
			if (isset($this->attributes['taxCategory']) && $price->getTaxCategory() == "0")
			{
				$price->setTaxCategory($this->attributes['taxCategory']);
			}
			$price->setImportPriceValue($this->attributes['value']);
			unset($this->attributes['value']);
		}
		if (isset($this->attributes['oldValue']))
		{
			$price->setImportPriceOldValue($this->attributes['oldValue']);
			unset($this->attributes['oldValue']);
		}
		 
		return $price;
	}

	/**
	 * @return catalog_PriceService
	 */
	protected function getDocumentService()
	{
		return catalog_PriceService::getInstance();
	}
	
	/**
	 * @see import_ScriptDocumentElement::saveDocument()
	 */
	protected function saveDocument()
	{
		$document = $this->getPersistentDocument();
		if ($document instanceof catalog_persistentdocument_price)
		{
			$document->setProductId($this->getParentNodeId());
			catalog_PriceService::getInstance()->insertPrice($document);
		}
	}
}