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
		$this->addNewBOKitItems($document);
	}

	/**
	 *
	 * @param catalog_persistentdocument_kit $kit
	 */
	protected function addNewBOKitItems($kit)
	{
		$newProducts = $kit->getNewKitItemProductsDocument();
		$kit->setNewKitItemProductIds(null);

		$qtt = intval($kit->getNewKitItemQtt());
		$kis = catalog_KititemService::getInstance();
		foreach ($newProducts as $document)
		{
			$declinable = false;
			if ($document instanceof catalog_persistentdocument_product)
			{
				$product = $document;
			}
			else if ($document instanceof catalog_persistentdocument_declinedproduct)
			{
				$product = $document->getFirstDeclination();
				$declinable = true;
			}

			if ($product === null) {continue;}

			$kitItem = $this->getKitItemByProduct($kit, $product);
			if ($kitItem === null)
			{
				$kitItem = $kis->createNew($kit, $product, $qtt);
				$kitItem->setDeclinable($declinable);
				$kis->save($kitItem);
				$kit->addKititem($kitItem);
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
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @param integer[] $targetIds
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getPriceByTargetIds($kit, $shop, $billingArea, $targetIds, $quantity = 1)
	{
		if (!in_array($kit->getId(), $targetIds))
		{
			$targetIds[] = $kit->getId();
		}
		return $this->calculatePriceByTargetIds($kit, $shop, $billingArea, $targetIds, $quantity);
	}

	/**
	 * @param catalog_persistentdocument_kit $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @param integer[] $targetIds
	 * @return catalog_persistentdocument_price[]
	 */
	public function getPricesByTargetIds($product, $shop, $billingArea, $targetIds)
	{
		throw new Exception('Not implemented');
	}

	/**
	 * @param catalog_persistentdocument_kit $kit
	 * @param catalog_persistentdocument_shop $shop
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @param integer[] $targetIds
	 * @param float $quantity
	 * @return catalog_persistentdocument_lockedprice
	 */
	public function getItemsPriceByTargetIds($kit, $shop, $billingArea, $targetIds, $quantity = 1)
	{
		return $this->calculatePriceByTargetIds($kit, $shop, $billingArea, $targetIds, $quantity);
	}

	/**
	 * @param catalog_persistentdocument_kit $kit
	 * @param catalog_persistentdocument_shop $shop 
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @param integer[] $targetIds
	 * @param float $quantity
	 * @return catalog_persistentdocument_lockedprice
	 */
	protected function calculatePriceByTargetIds($kit, $shop, $billingArea, $targetIds, $quantity = 1)
	{
		$kitPrice = catalog_LockedpriceService::getInstance()->getNewDocumentInstance();
		$kitPrice->setLockedFor($kit->getId());
		$kitPrice->setToZero();
		$taxCategory = false;

		$kitPrice->setProductId($kit->getId());
		$kitPrice->setShopId($shop->getId());
		$kitPrice->setBillingAreaId($billingArea->getId());
		$kis = catalog_KititemService::getInstance();
		foreach ($kit->getKititemArray() as $kititem)
		{
			if (!$kis->appendPrice($kititem, $kitPrice, $shop, $billingArea, $targetIds, $quantity))
			{
				return null;
			}
			if ($taxCategory === false)
			{
				$taxCategory = $kitPrice->getTaxCategory();
			}
			else if ($taxCategory != $kitPrice->getTaxCategory())
			{
				$taxCategory = '0';
			}
		}
		$kitPrice->setTaxCategory($taxCategory);
		if ($kitPrice->getValueWithoutTax() >= $kitPrice->getOldValueWithoutTax())
		{
			foreach ($kitPrice->getPricePartArray() as $pricePart)
			{
				/* @var $pricePart catalog_persistentdocument_lockedprice */
				$pricePart->setOldValueWithoutTax(null);
			}
			$kitPrice->setOldValueWithoutTax(null);
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
			if ($kitItem instanceof catalog_persistentdocument_kititem)
			{
				$kitItem->setCurrentKit($product);
				if ($kitItem->getDeclinable())
				{
					$kitItemProduct = $kitItem->getProduct();
					$key = $kitItem->getCurrentKey();
					if (isset($customItems[$key]))
					{
						try
						{
							$customProduct = DocumentHelper::getDocumentInstance($customItems[$key], 'modules_catalog/product');
							foreach ($kitItemProduct->getDeclinations() as $declination)
							{
								if($customProduct === $declination)
								{
									$kitItem->setCurrentProduct($customProduct);
								}
							}
						}
						catch (Exception $e)
						{
							Framework::warn(__METHOD__ . ' ' . $e->getMessage());
						}
					}
					if ($kitItem->getCurrentProduct() === null)
					{
						$kitItem->setDefaultProductForShop($shop);
					}
				}
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
			if ($kitItem->getDeclinable() && $kitItem->getCurrentProduct() !== null)
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
			if ($kitItem instanceof catalog_persistentdocument_kititem)
			{
				$kitItem->setCurrentKit($product);
				$key = $kitItem->getCurrentKey();
				if ($kitItem->getDeclinable())
				{
					$kitItemProduct = $kitItem->getProduct();
					$key = $kitItem->getCurrentKey();
					if (isset($properties[$key]))
					{
						try
						{
							$customProduct = catalog_persistentdocument_product::getInstanceById($properties[$key]);
							foreach ($kitItemProduct->getDeclinations() as $declination)
							{
								if ($customProduct === $declination)
								{
									$kitItem->setCurrentProduct($customProduct);
								}
							}
						}
						catch (Exception $e)
						{
							Framework::warn(__METHOD__ . ' ' . $e->getMessage());
						}
					}

					if ($kitItem->getCurrentProduct() === null)
					{
						$kitItem->setDefaultProductForShop(catalog_ShopService::getInstance()->getCurrentShop());
					}
				}
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

	/**
	 * @param catalog_persistentdocument_kit $product
	 * @param array $cartLineProperties
	 * @param order_persistentdocument_orderline $orderLine
	 * @param order_persistentdocument_order $order
	 */
	public function updateOrderLineProperties($product, &$cartLineProperties, $orderLine, $order)
	{
		parent::updateOrderLineProperties($product, $cartLineProperties, $orderLine, $order);
		$shop = $order->getShop();
		$customer = $order->getCustomer();
		$cartLineProperties['variance'] = $product->getCodeReference();

		$targetIds = catalog_PriceService::getInstance()->convertCustomerToTargetIds($customer);
		$targetIds[] = $product->getId();
		
		foreach ($product->getKititemArray() as $kititem)
		{
			if ($kititem instanceof catalog_persistentdocument_kititem)
			{
				$kititemPrice = $kititem->getDocumentService()->getKititemPrice($kititem, $shop, $targetIds, $orderLine->getQuantity());
				$kititemProduct = $kititem->getDefaultProduct();
				$quantity = $kititem->getQuantity() * $orderLine->getQuantity();
				$netAmount = $kititemPrice->getValueWithTax() * $quantity;
				
				if ($kititemPrice->getOldValueWithoutTax())
				{
					$discount = $kititemPrice->getValueWithoutTax() / $kititemPrice->getOldValueWithoutTax();
				}
				else
				{
					$discount = 1;
				}

				$amountWithTax = $netAmount * $discount;

				$cartLineProperties['kititems'][$kititem->getId()]['codeReference'] = $kititemProduct->getCodeReference();
				$cartLineProperties['kititems'][$kititem->getId()]['designation'] = $kititemProduct->getLabel();
				$cartLineProperties['kititems'][$kititem->getId()]['quantity'] = $quantity;
				$cartLineProperties['kititems'][$kititem->getId()]['netAmount'] = catalog_PriceFormatter::getInstance()->round($netAmount, $order->getCurrencyCode());
				$cartLineProperties['kititems'][$kititem->getId()]['discount'] = number_format((1 - $discount) * 100,1);
				$cartLineProperties['kititems'][$kititem->getId()]['amountWithTax'] = catalog_PriceFormatter::getInstance()->round($amountWithTax, $order->getCurrencyCode());
			}
		}
		return;
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
		return array('catalog/kit' => array(
			'label' => 'label',
			'brand' => 'brand/label',
			'codeReference' => 'codeReference'
		));
	}
}