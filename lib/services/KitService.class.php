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
			if (DocumentHelper::isEquals($product, $kitItem->getProduct()))
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
						$statusInfo = f_Locale::translateUI('&modules.catalog.bo.general.Product-not-published;', array('label' => $product->getVoLabel()));
						$this->setActivePublicationStatusInfo($document, $statusInfo);
						return false;
					}
				}
			}
			else
			{
				$statusInfo = f_Locale::translateUI('&modules.catalog.bo.general.Has-no-item;');
				$this->setActivePublicationStatusInfo($document, $statusInfo);
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
		}
	}

	
	/**
	 * @param catalog_persistentdocument_kit $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preInsert($document, $parentNodeId = null)
//	{
//		parent::preInsert($document, $parentNodeId);
//	}

	
	/**
	 * @param catalog_persistentdocument_kit $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postSave($document, $parentNodeId = null)
//	{
//		parent::postSave($document, $parentNodeId);
//	}
	
	/**
	 * @param catalog_persistentdocument_kit $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postInsert($document, $parentNodeId = null)
//	{
//		parent::postInsert($document, $parentNodeId);
//	}

	/**
	 * @param catalog_persistentdocument_kit $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preUpdate($document, $parentNodeId = null)
//	{
//		parent::preUpdate($document, $parentNodeId);
//	}


	/**
	 * @param catalog_persistentdocument_kit $document
	 * @return void
	 */
//	protected function preDelete($document)
//	{
//		parent::preDelete($document);
//	}

	/**
	 * @param catalog_persistentdocument_kit $document
	 * @return void
	 */
//	protected function preDeleteLocalized($document)
//	{
//		parent::preDeleteLocalized($document);
//	}

	/**
	 * @param catalog_persistentdocument_kit $document
	 * @return void
	 */
//	protected function postDelete($document)
//	{
//		parent::postDelete($document);
//	}

	/**
	 * @param catalog_persistentdocument_kit $document
	 * @return void
	 */
//	protected function postDeleteLocalized($document)
//	{
//		parent::postDeleteLocalized($document);
//	}


	/**
	 * Methode à surcharger pour effectuer des post traitement apres le changement de status du document
	 * utiliser $document->getPublicationstatus() pour retrouver le nouveau status du document.
	 * @param catalog_persistentdocument_kit $document
	 * @param String $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
//	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
//	{
//		parent::publicationStatusChanged($document, $oldPublicationStatus, $params);
//	}

	/**
	 * Correction document is available via $args['correction'].
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Array<String=>mixed> $args
	 */
//	protected function onCorrectionActivated($document, $args)
//	{
//		parent::onCorrectionActivated($document, $args);
//	}

	/**
	 * @param catalog_persistentdocument_kit $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagAdded($document, $tag)
//	{
//		parent::tagAdded($document, $tag);
//	}

	/**
	 * @param catalog_persistentdocument_kit $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagRemoved($document, $tag)
//	{
//		parent::tagRemoved($document, $tag);
//	}

	/**
	 * @param catalog_persistentdocument_kit $fromDocument
	 * @param f_persistentdocument_PersistentDocument $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedFrom($fromDocument, $toDocument, $tag)
//	{
//		parent::tagMovedFrom($fromDocument, $toDocument, $tag);
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $fromDocument
	 * @param catalog_persistentdocument_kit $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedTo($fromDocument, $toDocument, $tag)
//	{
//		parent::tagMovedTo($fromDocument, $toDocument, $tag);
//	}

	/**
	 * Called before the moveToOperation starts. The method is executed INSIDE a
	 * transaction.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $destId
	 */
//	protected function onMoveToStart($document, $destId)
//	{
//		parent::onMoveToStart($document, $destId);
//	}

	/**
	 * @param catalog_persistentdocument_kit $document
	 * @param Integer $destId
	 * @return void
	 */
//	protected function onDocumentMoved($document, $destId)
//	{
//		parent::onDocumentMoved($document, $destId);
//	}

	/**
	 * this method is call before saving the duplicate document.
	 * If this method not override in the document service, the document isn't duplicable.
	 * An IllegalOperationException is so launched.
	 *
	 * @param catalog_persistentdocument_kit $newDocument
	 * @param catalog_persistentdocument_kit $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function preDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//		throw new IllegalOperationException('This document cannot be duplicated.');
//	}

	/**
	 * this method is call after saving the duplicate document.
	 * $newDocument has an id affected.
	 * Traitment of the children of $originalDocument.
	 *
	 * @param catalog_persistentdocument_kit $newDocument
	 * @param catalog_persistentdocument_kit $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function postDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//	}

	/**
	 * Returns the URL of the document if has no URL Rewriting rule.
	 *
	 * @param catalog_persistentdocument_kit $document
	 * @param string $lang
	 * @param array $parameters
	 * @return string
	 */
//	public function generateUrl($document, $lang, $parameters)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_kit $document
	 * @return integer | null
	 */
//	public function getWebsiteId($document)
//	{
//		return parent::getWebsiteId($document);
//	}

	/**
	 * @param catalog_persistentdocument_kit $document
	 * @return website_persistentdocument_page | null
	 */
//	public function getDisplayPage($document)
//	{
//		return parent::getDisplayPage($document);
//	}

	/**
	 * @param catalog_persistentdocument_kit $document
	 * @param string $bockName
	 * @return array with entries 'module' and 'template'. 
	 */
//	public function getSolrserachResultItemTemplate($document, $bockName)
//	{
//		return array('module' => 'catalog', 'template' => 'Catalog-Inc-KitResultDetail');
//	}
}