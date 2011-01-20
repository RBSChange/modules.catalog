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
	 * @return array
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
	
	/**
	 * @param catalog_persistentdocument_bundleproduct $bundle
	 * @param catalog_persistentdocument_shop $shop
	 * @param integer[] $targetIds
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getItemsPriceByTargetIds($bundle, $shop, $targetIds, $quantity = 1)
	{
		$itemsPrice = catalog_PriceService::getInstance()->getNewDocumentInstance();
		$itemsPrice->setToZero();
		$taxCode = false;
		
		$itemsPrice->setProductId($bundle->getId());
		$itemsPrice->setShopId($shop->getId());
		$bis = catalog_BundleditemService::getInstance();
		foreach ($bundle->getBundleditemArray() as $bundleitem) 
		{
			if (!$bis->appendPrice($bundleitem, $itemsPrice, $shop, $targetIds, $quantity))
			{
				return null;
			}
			if ($taxCode === false)
			{
				$taxCode = $itemsPrice->getTaxCode();
			}
			else if ($taxCode != $itemsPrice->getTaxCode())
			{
				$taxCode = null;
			}
		}
		$itemsPrice->setTaxCode($taxCode);
		if ($itemsPrice->getValueWithoutTax() >= $itemsPrice->getOldValueWithoutTax())
		{
			$itemsPrice->removeDiscount();
		}
		return $itemsPrice;	
	}
	
	/**
	 * @param catalog_persistentdocument_bundleproduct $product
	 * @param double $quantity
	 * @return double | null
	 */
	public function addStockQuantity($product, $quantity)
	{
		$stock = null;
		if ($product->getBundleditemCount())
		{
			$stSrv = catalog_StockService::getInstance();
			foreach ($product->getBundleditemArray() as $bundledItem) 
			{
				$itemStock = $stSrv->increaseQuantity($bundledItem->getProduct(), $quantity * $bundledItem->getQuantity());
				if ($itemStock !== null)
				{
					$newStock = $itemStock / $bundledItem->getQuantity();
					if ($stock === null || $newStock < $stock)
					{
						$stock = $newStock;
					}
				}
			}
		}
		return $stock;
		
	}

	/**
	 * @param catalog_persistentdocument_bundleproduct $product
	 * @return string
	 */
	public function getCurrentStockLevel($product)
	{
		if ($product->getBundleditemCount())
		{
			foreach ($product->getBundleditemArray() as $bundledItem) 
			{
				$stDoc = $bundledItem->getProduct()->getStockableDocument();
				if ($stDoc !== null)
				{
					if ($stDoc->getCurrentStockLevel() === catalog_StockService::LEVEL_UNAVAILABLE)
					{
						return catalog_StockService::LEVEL_UNAVAILABLE;
					}
				}
			}
			return catalog_StockService::LEVEL_AVAILABLE;
		}	
		return catalog_StockService::LEVEL_UNAVAILABLE;
	}

	/**
	 * @param catalog_persistentdocument_bundleproduct $product
	 * @return string
	 */
	public function getCurrentStockQuantity($product)
	{
		$quantity = null;
		if ($product->getBundleditemCount())
		{
			foreach ($product->getBundleditemArray() as $bundledItem) 
			{
				$stDoc = $bundledItem->getProduct()->getStockableDocument();
				if ($stDoc !== null)
				{
					$newStock = $stDoc->getCurrentStockQuantity();
					if ($newStock !== null && $bundledItem->getQuantity() > 0)
					{
						$realStock = $newStock / $bundledItem->getQuantity();
						if ($quantity === null || $quantity > $realStock)
						{
							$quantity = $realStock;
						}
					}
				}
			}
		}
		return $quantity;
	}
}