<?php
/**
 * @package modules.catalog
 * @method catalog_ProductdeclinationService getInstance()
 */
class catalog_ProductdeclinationService extends catalog_ProductService
{
	/**
	 * @return catalog_persistentdocument_productdeclination
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/productdeclination');
	}

	/**
	 * Create a query based on 'modules_catalog/productdeclination' model.
	 * Return document that are instance of modules_catalog/productdeclination,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/productdeclination');
	}
	
	/**
	 * Create a query based on 'modules_catalog/productdeclination' model.
	 * Only documents that are strictly instance of modules_catalog/productdeclination
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/productdeclination', false);
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 * @param boolean $ordered
	 * @return catalog_persistentdocument_productdeclination[]
	 */
	public function getArrayByDeclinedProduct($declinedProduct, $ordered = true)
	{
		$query = $this->createQuery()->add(Restrictions::eq('declinedproduct', $declinedProduct));
		if ($ordered)
		{
			$query->addOrder(Order::asc('indexInDeclinedproduct'));
		}
		return $query->find();
	}
		
	/**
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 * @param boolean $ordered
	 * @return catalog_persistentdocument_productdeclination[]
	 */
	public function getPublishedArrayByDeclinedProduct($declinedProduct, $ordered = true)
	{
		$query = $this->createQuery()
			->add(Restrictions::eq('declinedproduct', $declinedProduct))
			->add(Restrictions::published());
		if ($ordered)
		{
			$query->addOrder(Order::asc('indexInDeclinedproduct'));
		}
		return $query->find();
	}	
	
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 * @param boolean $published
	 * @return integer
	 */
	public function getCountByDeclinedProduct($declinedProduct, $published = false)
	{
		$query = $this->createQuery()
			->add(Restrictions::eq('declinedproduct', $declinedProduct))
			->setProjection(Projections::rowCount('count'));
		if ($published)
		{
			$query->add(Restrictions::published());
		}
		$result = $query->find();
		return is_array($result) ? intval($result[0]['count']): 0;
	}		
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 * @return integer[]
	 */
	public function getIdArrayByDeclinedProduct($declinedProduct)
	{
		$query = $this->createQuery()->add(Restrictions::eq('declinedproduct', $declinedProduct))
			->setProjection(Projections::property('id', 'id'));
		return $query->findColumn('id');
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 * @return catalog_persistentdocument_productdeclination
	 */
	public function getFirstByDeclinedProduct($declinedProduct)
	{
		$results = $this->createQuery()->add(Restrictions::eq('declinedproduct', $declinedProduct))
					->addOrder(Order::asc('indexInDeclinedproduct'))
					->setMaxResults(1)
					->find();
		return (count($results)) ? $results[0] : null;
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 * @param boolean $publishedOnly
	 * @return array('id', 'axe1', 'axe2', 'axe3')
	 */
	public function getIdAndAxesArrayByDeclinedProduct($declinedProduct, $publishedOnly = false)
	{
		$query = $this->createQuery()
			->add(Restrictions::eq('declinedproduct', $declinedProduct))
			->setProjection(Projections::property('id'), Projections::property('axe1'), Projections::property('axe2'), Projections::property('axe3'));
		if ($publishedOnly)
		{
			$query->add(Restrictions::published());
		}
		$query->addOrder(Order::asc('indexInDeclinedproduct'));
		return $query->find();
	}
	

	/**
	 * Synchronize properties shelf | brand | description | serializedattributes | shippingModeId |
	 * 		| pageTitle | pageDescription | pageKeywords
	 * @param catalog_persistentdocument_productdeclination $declination
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 */
	protected function synchronizePropertiesByDeclinedProduct($declination, $declinedProduct)
	{
		$syncPropModified = $declinedProduct->getDocumentService()->getSynchronizedPropertiesName();
		$cms = catalog_ModuleService::getInstance();
		foreach ($syncPropModified as $name) 
		{
			switch ($name) 
			{
				case 'shelf':
					if (!DocumentHelper::documentArrayEquals($declination->getShelfArray(), $declinedProduct->getShelfArray()))
					{
						$declination->setShelfArray($declinedProduct->getShelfArray());
					}
					break;
				case 'pictogram':
					if (!DocumentHelper::documentArrayEquals($declination->getPictogramArray(), $declinedProduct->getPictogramArray()))
					{
						$declination->setPictogramArray($declinedProduct->getPictogramArray());
					}
					break;
				case 'brand': $declination->setBrand($declinedProduct->getBrand()); break;
				case 'description': $declination->setDescription($declinedProduct->getDescription()); break;
				case 'serializedattributes': $declination->setSerializedattributes($declinedProduct->getSerializedattributes());  break;
				case 'shippingModeId': $declination->setShippingModeId($declinedProduct->getShippingModeId()); break;
				case 'pageTitle': $declination->setPageTitle($declinedProduct->getPageTitle()); break;
				case 'pageDescription': $declination->setPageDescription($declinedProduct->getPageDescription()); break;
				case 'pageKeywords': $declination->setPageKeywords($declinedProduct->getPageKeywords()); break;
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		parent::preSave($document, $parentNodeId);
		if ($document->getDeclinedproduct() === null)
		{
			$declinedproduct = catalog_persistentdocument_declinedproduct::getInstanceById($parentNodeId);
			$document->setDeclinedproduct($declinedproduct);
		}
		$this->synchronizePropertiesByDeclinedProduct($document, $document->getDeclinedproduct());
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param integer $parentNodeId
	 */
	protected function preInsert($document, $parentNodeId)
	{
		if ($document->getIndexInDeclinedproduct() < 0)
		{
			$index = $this->getCountByDeclinedProduct($document->getDeclinedproduct());
			$document->setIndexInDeclinedproduct($index);
		}
	}
		
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		$declinedProduct = $document->getDeclinedproduct();
		if ($declinedProduct->getSynchronizePrices())
		{
			$prices = catalog_PriceService::getInstance()->createQuery()
				->add(Restrictions::eq('productId', $declinedProduct->getId()))->find();
			$this->copyPricesOnDeclinationIds($prices, array($document->getId()));
		}

		$this->populatesAxes($document);
		if ($document->isModified())
		{
			$this->getPersistentProvider()->updateDocument($document);
		}
		
		$declinedProduct->getDocumentService()->declinationAdded($declinedProduct, $document);		
		parent::postInsert($document, $parentNodeId);
	}	
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preUpdate($document, $parentNodeId)
	{
		$this->populatesAxes($document);
	}

	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 */
	protected function preDelete($document)
	{
		parent::preDelete($document);
		catalog_CrossitemService::getInstance()->setNeedCompileByTargetOrLinkedDocument($document->getDeclinedproduct());
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 */
	public function isPublishable($document)
	{
		$result = parent::isPublishable($document);
		if ($result)
		{
			if (!$document->getDeclinedproduct()->isPublished())
			{
				$this->setActivePublicationStatusInfo($document, '&m.catalog.document.declinedproduct.not-published;');
				$result = false;
			}
		}
		return $result;
	}

	/**
	 * @param catalog_persistentdocument_price[] $prices
	 * @param integer[] $declinationIds
	 */
	public function copyPricesOnDeclinationIds($prices, $declinationIds)
	{
		try 
		{
			$this->getTransactionManager()->beginTransaction();
			foreach ($prices as $price) 
			{
				if (!$price->hasMeta(catalog_PriceService::META_IS_REPLICATED))
				{
					$price->setMeta(catalog_PriceService::META_IS_REPLICATED, '1');
					$price->saveMeta();
				}
				
				foreach ($declinationIds as $declinationId)
				{
					$newPrices = $this->copyPriceOnDeclinationId($price, $declinationId);
				}
			}
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
			throw $e;
		}
	}
	
	
	
	/**
	 * @param catalog_persistentdocument_price $price
	 * @param integer $declinationId
	 * @return catalog_persistentdocument_lockedprice
	 */
	protected function copyPriceOnDeclinationId($price, $declinationId)
	{
		$newPrice = catalog_LockedpriceService::getInstance()->getNewDocumentInstance();
		$price->copyPropertiesTo($newPrice, true);
		$newPrice->setMeta("f_tags", array());
		$newPrice->setAuthor(null);
		$newPrice->setAuthorid(null);
		$newPrice->setCreationdate(null);
		$newPrice->setModificationdate(null);
		$newPrice->setDocumentversion(0);
		$newPrice->setProductId($declinationId);
		$newPrice->setLockedFor($price->getId());
		$newPrice->save();	
		return $newPrice;
	}	
	
	/**
	 * @param catalog_persistentdocument_productdeclination $productDeclination
	 */
	protected function populatesAxes($productDeclination)
	{
		$declinedProduct = $productDeclination->getDeclinedproduct();
		foreach ($declinedProduct->getAxes() as $axe) 
		{
			if ($axe instanceof catalog_ProductAxe) 
			{
				$axe->populateAxeValue($productDeclination);
			}
		}
		if ($productDeclination->isPropertyModified('axe1') || $productDeclination->isPropertyModified('axe2') || $productDeclination->isPropertyModified('axe3'))
		{
			catalog_CrossitemService::getInstance()->setNeedCompileByTargetOrLinkedDocument($declinedProduct);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $product
	 * @param catalog_persistentdocument_compiledproduct $compiledProduct
	 */
	public function updateCompiledProduct($product, $compiledProduct)
	{
		parent::updateCompiledProduct($product, $compiledProduct);
		$declinationId = intval($product->getId());
		
		$data = $product->getDeclinedProduct()->getShowInListInfos();
		$found = false;
		foreach ($data as $grpIndex => $declinationIds) 
		{
			if (in_array($declinationId, $declinationIds, true))
			{
				$found = true;
				break;
			}
		}
		
		if (!$found)
		{
			Framework::warn(__METHOD__ . ' Declination not found :' . $declinationId);
			$compiledProduct->setShowInList(false);
			return;
		}
			
		$showInList = array();
		$subPotentialDeclination = null;
		foreach ($declinationIds as $subDeclinationId) 
		{
			$cp = $this->getCompiledDeclinationProduct($compiledProduct->getLang(), $compiledProduct->getTopicId(), $subDeclinationId);
			if ($cp === null) {continue;}
			if ($subPotentialDeclination === null && $this->shouldBeChoosenToShowInList($cp))
			{
				$subPotentialDeclination = $subDeclinationId;
			}
			if ($cp->getShowInList()) 
			{
				$showInList[] = $subDeclinationId;
			}
		}
		
		if ($subPotentialDeclination === null)
		{
			$subPotentialDeclination = f_util_ArrayUtils::firstElement($declinationIds);
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' count(' . count($showInList) . ') ShowInList DEFAULT: ' .$subPotentialDeclination . ' ->  Axe Ids: '. implode(', ', $declinationIds));
			}
		}
		else
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' count(' . count($showInList) . ') ShowInList: ' .$subPotentialDeclination . ' ->  Axe Ids: '. implode(', ', $declinationIds));
			}
		}
		
		$compiledProduct->setShowInList(($subPotentialDeclination == $declinationId));
		$needsCompiles = array();
		
		if (count($showInList) == 0)
		{
			if ($subPotentialDeclination != $declinationId)
			{
				$needsCompiles[] = $subPotentialDeclination;
			}
		} 
		else
		{
			
			if ($showInList[0] != $subPotentialDeclination)
			{
				if ($subPotentialDeclination == $declinationId)
				{
					$needsCompiles[] = $showInList[0];
				}
				else
				{
					$needsCompiles[] = $subPotentialDeclination;
				}
			}
			
			if (count($showInList) > 1)
			{
				foreach (array_slice($showInList, 1) as $id) 
				{
					if ($id !== $declinationId && !in_array($id, $needsCompiles))
					{
						$needsCompiles[] = $id;
					}
				}
			}
		}
		
		if (count($needsCompiles))
		{
			$this->setNeedCompile($needsCompiles);	
		}
	}
	
	/**
	 * To choose the declination to show in list, we first call successively this method on each displayable declination. The first declination that
	 * returns true is choosen. If no declination returns true, the first displayable declination is choosen.
	 * @see updateCompiledProduct()
	 * @param catalog_persistentdocument_compiledproduct $cp
	 * @return boolean
	 */
	protected function shouldBeChoosenToShowInList($cp)
	{
		return $cp->getPublicationCode() == 0 && $cp->getIsAvailable();
	}
	
	/**
	 * @param string $lang
	 * @param integer $topicId
	 * @param integer $declinationId
	 * @return catalog_persistentdocument_compiledproduct
	 */
	private function getCompiledDeclinationProduct($lang, $topicId, $declinationId)
	{
		return catalog_CompiledproductService::getInstance()->createQuery()
				->add(Restrictions::eq('lang', $lang))
				->add(Restrictions::eq('topicId', $topicId))
				->add(Restrictions::eq('product.id', $declinationId))
				->findUnique();
	}
	
	/**
	 * @param indexer_IndexedDocument $indexedDocument
	 * @param catalog_persistentdocument_compiledproduct $compildedProduct
	 * @param catalog_persistentdocument_productdeclination $product
	 * @param indexer_IndexService $indexService
	 */
	public function getIndexedDocumentByCompiledProduct(&$indexedDocument, $compildedProduct, $product, $indexService)
	{
		parent::getIndexedDocumentByCompiledProduct($indexedDocument, $compildedProduct, $product, $indexService);
		if ($compildedProduct->getShowInList())
		{
			$data = $product->getDeclinedProduct()->getShowInListInfos();
			foreach ($data as $grpIndex => $declinationIds)
			{
				if (in_array($product->getId(), $declinationIds, true))
				{
					$texts = array();
					foreach ($declinationIds as $declinationId)
					{
						if ($declinationId != $product->getId())
						{
							$ndec = DocumentHelper::getDocumentInstanceIfExists($declinationId);
							if ($ndec instanceof catalog_persistentdocument_productdeclination)
							{
								$text = $ndec->getDocumentService()->getHiddenInListIndexedText($ndec);
								if ($text)
								{
									$texts[] = $text;
								}
							}
						}
					}
					if (count($texts))
					{
						$indexedDocument->setText($indexedDocument->getText() . ' ' . implode(' ', $texts));
					}
				}
			}
		}		
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $product
	 * @return string
	 */
	public function getHiddenInListIndexedText($product)
	{
		return $product->getCodeReference();
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
		return array(
			'catalog/productdeclination' => array(
				'label' => 'label',
				'codeReference' => 'codeReference'
			),
			'catalog/declinedproduct' => array(
				'label' => 'declinedproduct/label',
				'brand' => 'declinedproduct/brand/label',
				'codeReference' => 'declinedproduct/codeReference'
			)
		);
	}

	/**
	 * @param catalog_persistentdocument_productdeclination $productdeclination
	 * @return catalog_persistentdocument_declinedproduct
	 */
	public function getTargetForComment($productdeclination)
	{
		return $productdeclination->getDeclinedproduct();
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_productdeclination[]
	 */
	public function getPublishedDeclinationsInShop($declinedProduct, $shop)
	{
		$query = $this->createQuery()
			->add(Restrictions::eq('declinedproduct', $declinedProduct))
			->addOrder(Order::asc('indexInDeclinedproduct'));
			
		$query->createCriteria('compiledproduct')
			->add(Restrictions::published())
			->add(Restrictions::eq('shopId', $shop->getId()));						
		return $query->find();
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_productdeclination
	 */
	public function getPublishedDefaultDeclinationInShop($declinedProduct, $shop)
	{
		return f_util_ArrayUtils::firstElement($this->getPublishedDeclinationsInShop($declinedProduct, $shop));
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
		$cartLineProperties['variance'] = $product->getDeclinedproduct()->getCodeReference();
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param integer $axeNumber
	 * @return string|null
	 */
	public function getNavigationLabel($document, $axeNumber = null)
	{
		$declined = $document->getDeclinedproduct();
		return $declined->getDocumentService()->getNavigationLabel($declined, $document, $axeNumber);
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $declination
	 * @param integer $axeNumber
	 * @return string
	 */
	public function getAxeLabel($declination, $axeNumber)
	{
		$axe = $declination->getDeclinedproduct()->{'getAxe'.$axeNumber}();
		$axeObject = catalog_ProductAxe::getInstanceByName($axe, $axeNumber);
		if (!$axeObject)
		{
			return null;
		}
		return $axeObject->getAxeValueLabel($declination);
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $declination
	 * @param integer $axeNumber
	 * @return string
	 */
	public function getAxeTitleAsHtml($declination, $axeNumber)
	{
		$axe = $declination->getDeclinedproduct()->{'getAxe'.$axeNumber}();
		$axeObject = catalog_ProductAxe::getInstanceByName($axe, $axeNumber);
		if (!$axeObject)
		{
			return null;
		}
		return $axeObject->getLabel();
	}

	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param integer $websiteId
	 * @return float|NULL
	 */
	public function getRatingAverage($document, $websiteId = null) 
	{
		if (catalog_ModuleService::getInstance()->areCommentsEnabled())
		{
			return comment_CommentService::getInstance()->getRatingAverageByTargetId($document->getDeclinedproduct()->getId(), $websiteId);
		}
		return null;			
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $cp
	 * @return catalog_persistentdocument_compiledproduct|null
	 */
	public function getShownInListByCompiledProduct($cp)
	{
		/* @var $declination catalog_persistentdocument_productdeclination */
		$declination = $cp->getProduct();
		$declined = $declination->getDeclinedproduct();
		$infos = $declined->getShowInListInfos();
		$declinationIds = array();
		foreach ($infos as $ids)
		{
			if (in_array($declination->getId(), $ids))
			{
				$declinationIds = $ids;
				break;
			}
		}
		if (!count($declinationIds))
		{
			return null;
		}
	
		return catalog_CompiledproductService::getInstance()->createQuery()->add(Restrictions::published())
		->add(Restrictions::eq('lang', $cp->getLang()))
		->add(Restrictions::eq('shopId', $cp->getShopId()))
		->add(Restrictions::eq('primary', true))
		->add(Restrictions::eq('showInList', true))
		->add(Restrictions::in('product.id', $declinationIds))->findUnique();
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param String $oldPublicationStatus
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		parent::publicationStatusChanged($document, $oldPublicationStatus, $params);
	
		// Republish kits.
		if (!isset($params['cause']) || $params["cause"] != "delete")
		{
			if ($document->isPublished() || $oldPublicationStatus == 'PUBLICATED')
			{
				$declined = $document->getDeclinedproduct();
				$declined->getDocumentService()->declinationPublicationStatusChanged($declined, $document, $oldPublicationStatus, $params);
			}
		}
	}
}