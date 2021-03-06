<?php
/**
 * catalog_BundleproductService
 * @package modules.catalog
 */
class catalog_BundleproductService extends catalog_ProductService
{
	/**
	 * @var catalog_BundleproductService
	 */
	private static $instance;

	/**
	 * @return catalog_BundleproductService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return catalog_persistentdocument_bundleproduct
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/bundleproduct');
	}

	/**
	 * Create a query based on 'modules_catalog/bundleproduct' model.
	 * Return document that are instance of modules_catalog/bundleproduct,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/bundleproduct');
	}
	
	/**
	 * Create a query based on 'modules_catalog/bundleproduct' model.
	 * Only documents that are strictly instance of modules_catalog/bundleproduct
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/bundleproduct', false);
	}
	
	/**
	 * @param catalog_persistentdocument_bundleproduct $bundleProduct
	 * @param catalog_persistentdocument_product $product
	 * @return catalog_persistentdocument_bundleditem or null
	 */
	protected function getBundledItemByProduct($bundleProduct, $product)
	{
		foreach ($bundleProduct->getBundleditemArray() as $bundledItem) 
		{	
			if (DocumentHelper::equals($product, $bundledItem->getProduct()))
			{
				return $bundledItem;
			}
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId = null)
	{
		parent::preInsert($document, $parentNodeId);
		$newProducts = $document->getNewBundledItemProductsDocument();
		$bis = catalog_BundleditemService::getInstance();
		foreach ($newProducts as $product) 
		{
			$bundledItem = $this->getBundledItemByProduct($document, $product);
			if ($bundledItem === null)
			{
				$bundledItem = $bis->getNewDocumentInstance();
				$bundledItem->setQuantity(intval($document->getNewBundledItemQtt()));
				$bundledItem->setProduct($product);
				$bis->save($bundledItem);
				$document->addBundleditem($bundledItem);
			}
		}
		$document->resetNewBundledItemProducts();
		
	}
	
	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preUpdate($document, $parentNodeId = null)
	{	
		parent::preUpdate($document, $parentNodeId);
		$newProducts = $document->getNewBundledItemProductsDocument();
		$bis = catalog_BundleditemService::getInstance();
		foreach ($newProducts as $product) 
		{
			$bundledItem = $this->getBundledItemByProduct($document, $product);
			if ($bundledItem === null)
			{
				$bundledItem = $bis->getNewDocumentInstance();
				$bundledItem->setQuantity(intval($document->getNewBundledItemQtt()));
				$bundledItem->setProduct($product);
				$bis->save($bundledItem);
				$document->addBundleditem($bundledItem);
			}
		}
		$document->resetNewBundledItemProducts();	
	}	
	
	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postUpdate($document, $parentNodeId = null)
	{
		parent::postUpdate($document, $parentNodeId);
		$bundledItemsToDelete = $document->getBundledItemsToDelete();
		foreach ($bundledItemsToDelete as $bundledItem) 
		{
			$bundledItem->delete();
		}
	}
		
	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		$result = parent::isPublishable($document);
		if ($result)
		{
			if ($document->getBundleditemCount())
			{
				foreach ($this->getBundledProductArray($document) as $product) 
				{
					if (!$product->isPublished())
					{
						$this->setActivePublicationStatusInfo($document, '&modules.catalog.bo.general.Product-not-published;', array('label' => $product->getVoLabel()));
						return false;
					}
				}
			}
			else
			{
				$this->setActivePublicationStatusInfo($document, '&modules.catalog.bo.general.Has-no-item;');
				return false;
			}
		}
		return $result;
	}
	
	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @return catalog_persistentdocument_product[]
	 */
	protected function getBundledProductArray($document)
	{
		$result = array();
		if ($document->getBundleditemCount())
		{
			foreach ($document->getBundleditemArray() as $bundledItem) 
			{
				$result[] = $bundledItem->getProduct();
			}	
		}
		return $result; 
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param catalog_persistentdocument_product $product
	 * @return integer[]
	 */
	public function getDisplayableIdsByContainedProduct($shop, $product)
	{
		$query = $this->createStrictQuery()->add(Restrictions::published())->add(Restrictions::eq('bundleditem.product', $product));
		$query->createCriteria('compiledproduct')->add(Restrictions::published())->add(Restrictions::eq('shopId', $shop->getId()));
		return $query->setProjection(Projections::property('id'))->findColumn('id');
	}
	
	/**
	 * @param catalog_persistentdocument_bundleproduct $bundle
	 * @param catalog_persistentdocument_shop $shop
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @param integer[] $targetIds
	 * @param float $quantity
	 * @return catalog_persistentdocument_price|null
	 */
	public function getItemsPriceByTargetIds($bundle, $shop, $billingArea, $targetIds, $quantity = 1)
	{
		$itemsPrice = catalog_PriceService::getInstance()->getNewDocumentInstance();
		$itemsPrice->setToZero();
		$taxCategory = false;
		
		$itemsPrice->setProductId($bundle->getId());
		$itemsPrice->setShopId($shop->getId());
		$itemsPrice->setBillingAreaId($billingArea->getId());
		foreach ($bundle->getBundleditemArray() as $bundleitem) 
		{
			/* @var $bundleitem catalog_persistentdocument_bundleditem */
			if (!$bundleitem->getDocumentService()->appendPrice($bundleitem, $itemsPrice, $shop, $billingArea, $targetIds, $quantity))
			{
				return null;
			}
			if ($taxCategory === false)
			{
				$taxCategory = $itemsPrice->getTaxCategory();
			}
			else if ($taxCategory != $itemsPrice->getTaxCategory())
			{
				$taxCategory = null;
			}
		}
		$itemsPrice->setTaxCategory($taxCategory);
		if ($itemsPrice->getValueWithoutTax() >= $itemsPrice->getOldValueWithoutTax())
		{
			$itemsPrice->removeDiscount();
		}
		return $itemsPrice;	
	}
	
	/**
	 * @return boolean
	 * @see catalog_ModuleService::getProductModelsThatMayAppearInCarts()
	 */
	public function mayAppearInCarts()
	{
		return true;
	}
	
	/**
	 * @return array
	 */
	public function getPropertyNamesForMarkergas()
	{
		return array('catalog/bundleproduct' => array(
			'label' => 'label',
			'brand' => 'brand/label',
			'codeReference' => 'codeReference'
		));
	}
	
	// Deprecated.
	
	/**
	 * @deprecated use getDisplayableIdsByContainedProduct
	 */
	public function getByBundledProduct($shop, $product)
	{
		$query = $this->createStrictQuery()
		->add(Restrictions::published())
		->add(Restrictions::eq('bundleditem.product', $product));
	
		$query->createCriteria('compiledproduct')
		->add(Restrictions::published())
		->add(Restrictions::eq('shopId', $shop->getId()));
	
		return $query->find();
	}
}