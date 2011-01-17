<?php
/**
 * catalog_KitService
 * @package modules.catalog
 */
class catalog_KitService extends catalog_ProductService
{
	/**
	 * @var catalog_KitService
	 */
	private static $instance;
	

	/**
	 * @return catalog_KitService
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
	 * @return catalog_persistentdocument_kit
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/kit');
	}

	/**
	 * Create a query based on 'modules_catalog/kit' model.
	 * Return document that are instance of modules_catalog/kit,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/kit');
	}
	
	/**
	 * Create a query based on 'modules_catalog/kit' model.
	 * Only documents that are strictly instance of modules_catalog/kit
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/kit', false);
	}
	
	/**
	 * @param catalog_persistentdocument_kit $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		parent::preSave($document, $parentNodeId);
		$newProducts = $document->getNewKitItemProductsDocument();
		$document->setNewKitItemProductIds(null);
		
		$qtt = intval($document->getNewKitItemQtt());
		$kis = catalog_KititemService::getInstance();
		foreach ($newProducts as $product)
		{
			$kitItem = $this->getKitItemByProduct($document, $product);
			if ($kitItem === null)
			{
				$kitItem = $kis->createNew($document, $product, $qtt);
				$kis->save($kitItem);
				$document->addKititem($kitItem);
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_kit $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postUpdate($document, $parentNodeId = null)
	{
		parent::postUpdate($document, $parentNodeId);
		$kitItemsToDelete = $document->getKitItemsToDelete();
		if (count($kitItemsToDelete))
		{
			$kis = catalog_KititemService::getInstance();
			foreach ($kitItemsToDelete as $kitItem) 
			{
				$kis->delete($kitItem);
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_kit $kit
	 * @param catalog_persistentdocument_product $product
	 * @return catalog_persistentdocument_bundleditem or null
	 */
	protected function getKitItemByProduct($kit, $product)
	{
		foreach ($kit->getKititemArray() as $kitItem) 
		{	
			if (DocumentHelper::equals($product, $kitItem->getProduct()))
			{
				return $kitItem;
			}
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_kit $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		$result = parent::isPublishable($document);
		if ($result)
		{
			if ($document->getKititemCount())
			{
				foreach ($document->getKititemArray() as $kitItem) 
				{
					$product = $kitItem->getProduct();
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
	 * @param catalog_persistentdocument_kit $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$resume = parent::getResume($document, $forModuleName, $allowedSections);
		$resume['properties']['itemscount'] = $document->getKititemCount();
		return $resume;
	}
	
	/**
	 * @see catalog_ProductService::getPriceByTargetIds()
	 *
	 * @param catalog_persistentdocument_kit $kit
	 * @param catalog_persistentdocument_shop $shop
	 * @param integer[] $targetIds
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getPriceByTargetIds($kit, $shop, $targetIds, $quantity = 1)
	{
		if (!in_array($kit->getId(), $targetIds))
		{
			$targetIds[] = $kit->getId();
		}		
		return $this->calculatePriceByTargetIds($kit, $shop, $targetIds, $quantity);	
	}
	
	/**
	 * @param catalog_persistentdocument_kit $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param integer[] $targetIds
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price[]
	 */
	public function getPricesByTargetIds($product, $shop, $targetIds)
	{
		throw new Exception('Not implemented');
	}
	
	/**
	 * @param catalog_persistentdocument_kit $kit
	 * @param catalog_persistentdocument_shop $shop
	 * @param integer[] $targetIds
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getItemsPriceByTargetIds($kit, $shop, $targetIds, $quantity = 1)
	{
		return $this->calculatePriceByTargetIds($kit, $shop, $targetIds, $quantity);
	}
	
	/**
	 * @param catalog_persistentdocument_kit $kit
	 * @param catalog_persistentdocument_shop $shop
	 * @param integer[] $targetIds
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	protected function calculatePriceByTargetIds($kit, $shop, $targetIds, $quantity = 1)
	{
		$kitPrice = catalog_PriceService::getInstance()->getNewDocumentInstance();
		$kitPrice->setToZero();
		$taxCode = false;
		
		$kitPrice->setProductId($kit->getId());
		$kitPrice->setShopId($shop->getId());
		$kis = catalog_KititemService::getInstance();
		foreach ($kit->getKititemArray() as $kititem) 
		{
			if (!$kis->appendPrice($kititem, $kitPrice, $shop, $targetIds, $quantity))
			{
				return null;
			}
			if ($taxCode === false)
			{
				$taxCode = $kitPrice->getTaxCode();
			}
			else if ($taxCode != $kitPrice->getTaxCode())
			{
				$taxCode = null;
			}
		}
		$kitPrice->setTaxCode($taxCode);
		if ($kitPrice->getValueWithoutTax() >= $kitPrice->getOldValueWithoutTax())
		{
			$kitPrice->removeDiscount();
		}
		return $kitPrice;		
	}
	
	/**
	 * @param catalog_persistentdocument_kit $product
	 * @param array $customItems
	 */
	public function setCustomItemsInfo($product, $shop, $customItems)
	{
		foreach ($product->getKititemArray() as $kitItem) 
		{
			$kitItem->setCurrentKit($product);
			$kitItemProduct = $kitItem->getProduct();
			
			$key = $kitItem->getCurrentKey();
			if (isset($customItems[$key]))
			{
				try 
				{
					$customProduct = DocumentHelper::getDocumentInstance($customItems[$key], 'modules_catalog/product');
					if ($customProduct instanceof catalog_persistentdocument_productdeclination) 
					{
						if ($customProduct->getRelatedDeclinedProduct() === $kitItemProduct)
						{
							$kitItem->setCurrentProduct($customProduct);
						}
						else
						{
							Framework::warn(__METHOD__ . ' Invalid product declination ' . $customProduct->__toString() . ' for kititem ' . $kitItem->__toString());
							$kitItem->setCurrentProduct($kitItemProduct->getDefaultDeclination($shop));
						}
					}
					else
					{
						Framework::warn(__METHOD__ . ' Invalid custom product type ' . get_class($customProduct) . ' for kititem ' . $kitItem->__toString());
					}

				}
				catch (Exception $e)
				{
					Framework::warn(__METHOD__ . ' ' . $e->getMessage());
				}
			}
			
			if ($kitItem->getCurrentProduct() === null && $kitItem->getProduct() instanceof catalog_persistentdocument_declinedproduct)
			{
				$kitItem->setCurrentProduct($kitItem->getProduct()->getDefaultDeclination($shop));
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_kit $product
	 * @return array
	 */
	public function getCustomItemsInfo($product)
	{
		$customItems = array();
		$kitItem = new catalog_persistentdocument_kititem();
		foreach ($product->getKititemArray() as $kitItem) 
		{
			if ($kitItem->getCurrentProduct() !== null)
			{
				$customItems['k'. $kitItem->getId()] = $kitItem->getCurrentProduct()->getId();
			}
		}
		return $customItems;
	}
	
	/**
	 * @see catalog_ProductService::getProductToAddToCart()
	 *
	 * @param catalog_persistentdocument_kit $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param Double $quantity
	 * @param array $properties
	 * @return catalog_persistentdocument_product
	 */
	public function getProductToAddToCart($product, $shop, $quantity, &$properties)
	{
		parent::getProductToAddToCart($product, $shop, $quantity, $properties);
		foreach ($this->getCustomItemsInfo($product) as $key => $value) 
		{
			$properties[$key] = $value;
		}
		return $product;
	}
	
	/**
	 * @see catalog_ProductService::updateProductFromCartProperties()
	 *
	 * @param catalog_persistentdocument_kit $product
	 * @param array $properties
	 */
	public function updateProductFromCartProperties($product, $properties)
	{
		foreach ($product->getKititemArray() as $kitItem) 
		{
			$kitItem->setCurrentKit($product);			
			$key = $kitItem->getCurrentKey();
			if (isset($properties[$key]))
			{
				try 
				{
					$customProduct = DocumentHelper::getDocumentInstance($properties[$key], 'modules_catalog/product');
					$kitItem->setCurrentProduct($customProduct);
				}
				catch (Exception $e)
				{
					Framework::warn(__METHOD__ . ' ' . $e->getMessage());
				}
			}
			else if ($kitItem->getProduct() instanceof catalog_persistentdocument_declinedproduct)
			{
				$kitItem->setCurrentProduct($kitItem->getProduct()->getDefaultDeclination());
			}
			else
			{
				$kitItem->setCurrentProduct(null);
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param array $properties
	 * @return void
	 */
	public function updateProductFromRequestParameters($product, $parameters)
	{
		if (array_key_exists('customitems', $parameters))
		{
			$customItems = $parameters['customitems'];
			if (!is_array($customItems))
			{
				$customItems = array();
			}
		}
		else
		{
			$customItems = array();
		}		
		$shop = catalog_ShopService::getInstance()->getCurrentShop(); 
		$this->setCustomItemsInfo($product, $shop, $customItems);
	}

	/**
	 * @param catalog_persistentdocument_kit $product
	 * @param double $quantity
	 * @return double | null
	 */
	public function addStockQuantity($product, $quantity)
	{
		$stock = null;
		if ($product->getKititemCount())
		{
			foreach ($product->getKititemArray() as $kitItem) 
			{
				$itemStock = catalog_StockService::getInstance()->increaseQuantity($kitItem->getProduct(), $quantity * $kitItem->getQuantity());
				if ($itemStock !== null)
				{
					$newStock = $itemStock / $kitItem->getQuantity();
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
	 * @param catalog_persistentdocument_kit $product
	 * @return string
	 */
	public function getCurrentStockLevel($product)
	{
		if ($product->getKititemCount())
		{
			foreach ($product->getKititemArray() as $kitItem) 
			{
				$stDoc = $kitItem->getDefaultProduct()->getStockableDocument();
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
	 * @param catalog_persistentdocument_kit $product
	 * @return string
	 */
	public function getCurrentStockQuantity($product)
	{
		$quantity = null;
		if ($product->getKititemCount())
		{
			foreach ($product->getKititemArray() as $kitItem) 
			{
				$stDoc = $kitItem->getDefaultProduct()->getStockableDocument();
				if ($stDoc !== null)
				{
					$newStock = $stDoc->getCurrentStockQuantity();
					if ($newStock !== null && $kitItem->getQuantity() > 0)
					{
						$realStock = $newStock / $kitItem->getQuantity();
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