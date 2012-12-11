<?php
/**
 * @package modules.catalog
 * @method catalog_KititemService getInstance()
 */
class catalog_KititemService extends f_persistentdocument_DocumentService
{
	/**
	 * @return catalog_persistentdocument_kititem
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/kititem');
	}

	/**
	 * Create a query based on 'modules_catalog/kititem' model.
	 * Return document that are instance of modules_catalog/kititem,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/kititem');
	}
	
	/**
	 * Create a query based on 'modules_catalog/kititem' model.
	 * Only documents that are strictly instance of modules_catalog/kititem
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/kititem', false);
	}
	
	/**
	 * @param catalog_persistentdocument_kit $kit
	 * @param catalog_persistentdocument_product $product
	 * @param integer $qtt
	 * @return catalog_persistentdocument_kititem
	 */
	public function createNew($kit, $product, $qtt = 1)
	{
		$item = $this->getNewDocumentInstance();
		$item->setQuantity($qtt);
		$item->setProduct($product);
		return $item;
	}
	
	/**
	 * @param catalog_persistentdocument_kititem $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		$product = $document->getProduct();
		if ($product == null)
		{
			$oldProduct = DocumentHelper::getDocumentInstanceIfExists($document->getProductOldValueId());
			$kit = array_pop($document->getKitArrayInverse());
			throw new Exception(LocaleService::getInstance()->trans('m.catalog.errors.kititem.cannot-delete-associated-product', array('ucf'), array(
				'product' => $oldProduct->getLabel(), 'kitname' => $kit->getLabel())));
		}
	}
	
	/**
	 * @param catalog_persistentdocument_kititem $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId)
	{
		if (f_util_StringUtils::isEmpty($document->getLabel()))
		{
			$document->setLabel($document->getProduct()->getVoLabel());	
		}
	}
	
	/**
	 * @param catalog_persistentdocument_kititem $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		if ($parentNodeId)
		{
			$pdoc = DocumentHelper::getDocumentInstance($parentNodeId);
			if ($pdoc instanceof catalog_persistentdocument_kit)
			{
				if ($pdoc->getIndexofKititem($document) === -1)
				{
					$pdoc->addKititem($document);
					$pdoc->save();
				}
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_kititem $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		$result = parent::isPublishable($document);
		if ($result)
		{
			$product = $document->getProduct();
			if ($document->getDeclinable() && $product instanceof catalog_persistentdocument_productdeclination)
			{
				$declined = $product->getDeclinedproduct();
				if (!$product->isPublished() && !catalog_ProductdeclinationService::getInstance()->getCountByDeclinedProduct($declined, true))
				{
					$this->setActivePublicationStatusInfo($document, '&modules.catalog.bo.general.No-published-declination;', array('label' => $declined->getVoLabel()));
					return false;
				}
			}
			else
			{
				if (!$product->isPublished())
				{
					$this->setActivePublicationStatusInfo($document, '&modules.catalog.bo.general.Product-not-published;', array('label' => $product->getVoLabel()));
					return false;
				}
			}
		}
		return $result;
	}
	
	/**
	 * @param catalog_persistentdocument_kititem $kititem
	 * @param catalog_persistentdocument_shop $shop 
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @param integer[] $targetIds
	 * @param float $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getKititemPrice($kititem, $shop, $billingArea, $targetIds, $quantity)
	{
		$product = $kititem->getDefaultProduct();
		return $product->getDocumentService()->getPriceByTargetIds($product, $shop, $billingArea, $targetIds, $quantity * $kititem->getQuantity());
		
	}
	/**
	 * @param catalog_persistentdocument_kititem $kititem
	 * @param catalog_persistentdocument_price $kitPrice
	 * @param catalog_persistentdocument_shop $shop 
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @param integer[] $targetIds
	 * @param float $quantity
	 * @return boolean
	 */
	public function appendPrice($kititem, $kitPrice, $shop, $billingArea, $targetIds, $quantity)
	{	
		$price = $this->getKititemPrice($kititem, $shop, $billingArea, $targetIds, $quantity);
		if ($price instanceof catalog_persistentdocument_price)
		{
			$priceItem = catalog_LockedpriceService::getInstance()->getNewDocumentInstance();
			$priceItem->setLockedFor($kititem->getId());
			$priceItem->setProductId($price->getProductId());
			$priceItem->setShopId($price->getShopId());
			$priceItem->setBillingAreaId($price->getBillingAreaId());
			$priceItem->setStoreWithTax($price->getStoreWithTax());
			
			$qtt = $kititem->getQuantity();
			$priceItem->setValue($price->getValue() * $qtt);
			$priceItem->setTaxCategory($price->getTaxCategory());
			if ($price->isDiscount())
			{
				$priceItem->setValueWithoutDiscount($price->getValueWithoutDiscount() * $qtt);
			}
			else
			{
				$priceItem->setValueWithoutDiscount($priceItem->getValue());
			}

			if ($price->getEcoTax() !== null)
			{
				$priceItem->setEcoTax($price->getEcoTax() * $qtt);
				$kitPrice->setEcoTax($kitPrice->getEcoTax() + $priceItem->getEcoTax());
			}
			
			$kitPrice->addPricePart($priceItem);
			
			$kitPrice->setValueWithoutTax($kitPrice->getValueWithoutTax() + $priceItem->getValueWithoutTax());	
			$kitPrice->setOldValueWithoutTax($kitPrice->getOldValueWithoutTax() + $priceItem->getOldValueWithoutTax());

			$kitPrice->setTaxCategory($price->getTaxCategory());
			return true;
		}
		else
		{
			Framework::warn(__METHOD__ . ' ' . var_export($kititem->getDefaultProduct(), true));
		}
		return false;
	}
	
	/**
	 * @param catalog_persistentdocument_kititem $kitItem
	 * @param array $result
	 * @return void
	 */
	public function transformToArray($kitItem, &$result)
	{
		$product = $kitItem->getProduct();	
		$showDeclination = false;
		$master = $product;
		if ($product instanceof catalog_DeclinableProduct)
		{
			if ($kitItem->getDeclinable())
			{
				$master = $product->getDeclinedproduct();
				if (!$master->getSynchronizePrices())
				{
					$showDeclination = true;
				}
			}
		}
		$statusOk = $master->isContextLangAvailable();
		$label = $statusOk ? $master->getLabel() : $master->getVoLabel();
		
		
		$result[] =  array(
				'id' => $kitItem->getId(),
				'productType' => str_replace('/', '_', $master->getDocumentModelName()),
				'productId' => $master->getId(),
				'productCodeReference' => $master->getCodeReference(),
				'actionrow' => $showDeclination ? '1' : '0',
				'statusOk' => $statusOk ? '': 'Non disponible',
				'label' => $label,
				'qtt' => $kitItem->getQuantity(),
		);	
			
		if ($showDeclination)
		{
			foreach ($product->getDeclinations() as $declination) 
			{				
				$declinationstatusOk = $declination->isContextLangAvailable();
				$declinationlabel = $declinationstatusOk ? $declination->getLabel() : $declination->getVoLabel();
				$result[] =  array(
					'id' => 0,
					'productType' => str_replace('/', '_', $declination->getDocumentModelName()),
					'productId' => $declination->getId(),
					'statusOk' => $declinationstatusOk ? '': 'Non disponible',
					'actionrow' => '2',
					'label' => $label,
					'sublabel' => $declinationlabel,
					'qtt' => '',
					'declinable' => false,
					'productCodeReference' => $declination->getCodeReference(),
				);
			}
		}
	}
	
	/**
	 * @param website_UrlRewritingService $urlRewritingService
	 * @param catalog_persistentdocument_kititem $document
	 * @param website_persistentdocument_website $website
	 * @param string $lang
	 * @param array $parameters
	 * @return f_web_Link | null
	 */
	public function getWebLink($urlRewritingService, $document, $website, $lang, $parameters)
	{
		$kit = $document->getCurrentKit();
		if ($kit)
		{
			if (!isset($parameters['catalogParam']))
			{
				$parameters['catalogParam'] = array('kititemid' => $document->getId());
			} 
			else
			{
				$parameters['catalogParam']['kititemid']  = $document->getId();
			}
			return $urlRewritingService->getDocumentLinkForWebsite($kit, $website, $lang, $parameters);
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_kititem $document
	 * @param String $oldPublicationStatus
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		if (!isset($params['cause']) || $params["cause"] != "delete")
		{
			if ($document->isPublished() || $oldPublicationStatus == 'PUBLICATED')
			{
				$kit = f_util_ArrayUtils::firstElement($document->getKitArrayInverse());
				if ($kit)
				{
					/* @var $kit catalog_persistentdocument_kit */
					$kit->getDocumentService()->publishIfPossible($kit->getId());
				}
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 */
	public function publishIfPossibleByProduct($document)
	{
		$items = $this->createQuery()->add(Restrictions::eq('product', $document))->add(Restrictions::eq('declinable', false))->find();
		foreach ($items as $item)
		{
			/* @var $kititem catalog_persistentdocument_kititem */
			$item->getDocumentService()->publishIfPossible($item->getId());
		}
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 */
	public function publishIfPossibleByDeclinedProduct($document)
	{
		$declinations = $document->getDeclinationArray();
		$items = $this->createQuery()->add(Restrictions::in('product', $declinations))->add(Restrictions::eq('declinable', true))->find();
		foreach ($items as $item)
		{
			/* @var $kititem catalog_persistentdocument_kititem */
			$item->getDocumentService()->publishIfPossible($item->getId());
		}
	}
}
