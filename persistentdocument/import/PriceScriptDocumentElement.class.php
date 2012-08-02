<?php
class catalog_PriceScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return catalog_PriceService
	 */
	protected function getDocumentService()
	{
		return catalog_PriceService::getInstance();
	}
	
	/**
	 * @return catalog_persistentdocument_price
	 */
	protected function initPersistentDocument()
	{
		return $this->getDocumentService()->getNewDocumentInstance();
	}
	
	/**
	 * shopId-refid, billingAreaId-refid, [productId-refid], taxCategory
	 * @return array
	 */
	protected function getDocumentProperties()
	{
		//shopId, productId, billingAreaId
		/* @var $price catalog_persistentdocument_price */
		$price = $this->getPersistentDocument();
		if (isset($this->attributes['shop-refid']))
		{
			Framework::warn('Derpecated shop-refid attribute use shopId-refid attribute on price node in file: "' . $this->script->getCurrentXmlFilePath(). '"');
			$this->attributes['shopId-refid'] = $this->attributes['shop-refid'];
			unset($this->attributes['shop-refid']);
		}
		
		$attr = $this->getComputedAttributes();
		if (!isset($attr['shopId']))
		{
			throw new Exception('Attribute "shopId" is required');
		}
		else
		{
			$shop = ($attr['shopId'] instanceof catalog_persistentdocument_shop) ? $attr['shopId'] : catalog_persistentdocument_shop::getInstanceById($attr['shopId']);
			unset($attr['shopId']);
			$price->setShopId($shop->getId());
		}
		
		$productId = (isset($attr['productId'])) ? $attr['productId'] : $this->getParentNodeId();
		unset($attr['productId']);
		$product = ($productId  instanceof f_persistentdocument_PersistentDocument) ? $productId : DocumentHelper::getDocumentInstance($productId);
		$price->setProductId($product->getId());
		
		if (!isset($attr['billingAreaId']))
		{
			$billingArea = $shop->getDefaultBillingArea();
			Framework::warn('Undefined billingAreaId attribute on price node in file: "' . $this->script->getCurrentXmlFilePath(). '"');
		}
		else
		{
			$billingArea = ($attr['billingAreaId'] instanceof catalog_persistentdocument_billingarea) ? $attr['billingAreaId'] : catalog_persistentdocument_billingarea::getInstanceById($attr['billingAreaId']);
			unset($attr['billingAreaId']);
		}
		$price->setBillingAreaId($billingArea->getId());	
		$price->setStoreWithTax($billingArea->getStorePriceWithTax());
		
		if (isset($attr['storeWithTax']))
		{
			if (($attr['storeWithTax'] == 'true') != $price->getStoreWithTax())
			{
				Framework::warn('Invalid storeWithTax value according to billingArea on price node in file: "' . $this->script->getCurrentXmlFilePath(). '"');
			}
			unset($attr['storeWithTax']);
		}
		$taxCategory = (isset($attr['taxCategory'])) ? intval($attr['taxCategory']) : 0;
		unset($attr['taxCategory']);
		
		$tax = catalog_TaxService::getInstance()->getTaxDocumentByKey($billingArea->getId(), $taxCategory, $billingArea->getDefaultZone());
		if ($tax === null)
		{
			throw new Exception('Tax not found for billingAreaId: ' . $billingArea->getId() . ', taxCategory: ' .$taxCategory . ', taxZone: ' .$billingArea->getDefaultZone());
		}
		$price->setTaxCategory($taxCategory);
		
		//DEPRECATED ATTRIBUTE
		if (isset($attr['taxCode']))
		{
			Framework::warn('Invalid taxCode attribute on price node in file: "' . $this->script->getCurrentXmlFilePath(). '"');
			unset($attr['taxCode']);
		}
		
		$this->setPriceValueFromAttr($price, $billingArea, $tax, $attr);
		return $attr;
		
	}
	
	/**
	 * valueWithTax|valueWithoutTax|value
	 * @param catalog_persistentdocument_price $price
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @param catalog_persistentdocument_tax $tax
	 * @param array $attr
	 */
	protected function setPriceValueFromAttr($price, $billingArea, $tax,  &$attr)
	{
		//value, valueWithTax valueWithoutTax
		$valueWithTax = $valueWithoutTax = null;
		if (isset($attr['valueWithTax']))
		{
			$valueWithTax = floatval($attr['valueWithTax']);
			unset($attr['valueWithTax']);
		}
		if (isset($attr['valueWithoutTax']))
		{
			$valueWithoutTax = floatval($attr['valueWithoutTax']);
			unset($attr['valueWithoutTax']);
		}
		
		if (isset($attr['value']))
		{
			if ($billingArea->getBoEditWithTax())
			{
				Framework::warn('Assume value attribute as valueWithTax on price node in file: "' . $this->script->getCurrentXmlFilePath(). '"');
				$valueWithTax = floatval($attr['value']);
			}
			else
			{
				Framework::warn('Assume value attribute as valueWithoutTax on price node in file: "' . $this->script->getCurrentXmlFilePath(). '"');
				$valueWithoutTax = floatval($attr['value']);
			}
			unset($attr['value']);
		}
		
		if ($valueWithoutTax !== null && $valueWithTax !== null)
		{
			if ($price->getStoreWithTax())
			{
				$valueWithoutTax = null;
			}
			else
			{
				$valueWithTax = null;
			}
			Framework::warn('Assigned valueWithoutTax and valueWithTax attribute on price node in file: "' . $this->script->getCurrentXmlFilePath(). '"');
		}
		
		if ($valueWithoutTax !== null)
		{
			if ($price->getStoreWithTax())
			{
				$price->setValue($tax->getDocumentService()->addTaxByRate($valueWithoutTax, $tax->getRate()));
			}
			else
			{
				$price->setValue($valueWithoutTax);
			}
		}
		elseif ($valueWithTax !== null)
		{
			if ($price->getStoreWithTax())
			{
				$price->setValue($valueWithTax);
			}
			else
			{
				$price->setValue($tax->getDocumentService()->removeTaxByRate($valueWithTax, $tax->getRate()));
			}
		}
		else
		{
			Framework::warn('No value defined assume 0 on price node in file: "' . $this->script->getCurrentXmlFilePath(). '"');
			$price->setValue(0);
		}
		
		
		//valueWithoutDiscount, oldValueWithTax oldValueWithoutTax, oldValue
		if (isset($attr['oldValue']))
		{
			$attr['valueWithoutDiscount'] = $attr['oldValue'];
			unset($attr['oldValue']);
		}
		
		$oldValueWithTax = $oldValueWithoutTax = null;
		if (isset($attr['oldValueWithTax']))
		{
			$oldValueWithTax = floatval($attr['oldValueWithTax']);
			unset($attr['oldValueWithTax']);
		}
		if (isset($attr['oldValueWithoutTax']))
		{
			$oldValueWithoutTax = floatval($attr['oldValueWithoutTax']);
			unset($attr['oldValueWithoutTax']);
		}
		
		if (isset($attr['valueWithoutDiscount']))
		{
			if ($billingArea->getBoEditWithTax())
			{
				Framework::warn('Assume valueWithoutDiscount attribute as oldValueWithTax on price node in file: "' . $this->script->getCurrentXmlFilePath(). '"');
				$oldValueWithTax = floatval($attr['valueWithoutDiscount']);
			}
			else
			{
				Framework::warn('Assume valueWithoutDiscount attribute as oldValueWithoutTax on price node in file: "' . $this->script->getCurrentXmlFilePath(). '"');
				$oldValueWithoutTax = floatval($attr['valueWithoutDiscount']);
			}
			unset($attr['valueWithoutDiscount']);
		}
		
		if ($oldValueWithTax !== null && $oldValueWithoutTax !== null)
		{
			if ($price->getStoreWithTax())
			{
				$oldValueWithoutTax = null;
			}
			else
			{
				$oldValueWithTax = null;
			}
			Framework::warn('Assigned oldValueWithoutTax and oldValueWithTax attribute on price node in file: "' . $this->script->getCurrentXmlFilePath(). '"');
		}
		
		if ($oldValueWithoutTax !== null)
		{
			if ($price->getStoreWithTax())
			{
				$price->setValueWithoutDiscount($tax->getDocumentService()->addTaxByRate($oldValueWithoutTax, $tax->getRate()));
			}
			else
			{
				$price->setValueWithoutDiscount($oldValueWithoutTax);
			}
		}
		elseif ($oldValueWithTax !== null)
		{
			if ($price->getStoreWithTax())
			{
				$price->setValueWithoutDiscount($oldValueWithTax);
			}
			else
			{
				$price->setValueWithoutDiscount($tax->getDocumentService()->removeTaxByRate($oldValueWithTax, $tax->getRate()));
			}
		}
	}
	
	/**
	 * @see import_ScriptDocumentElement::saveDocument()
	 */
	protected function saveDocument()
	{
		$document = $this->getPersistentDocument();
		if ($document instanceof catalog_persistentdocument_price)
		{
			catalog_PriceService::getInstance()->insertPrice($document);
		}
	}
}