<?php
/**
 * catalog_ProductdeclinationService
 * @package catalog
 */
class catalog_ProductdeclinationService extends catalog_ProductService
{
	/**
	 * @var catalog_ProductdeclinationService
	 */
	private static $instance;

	/**
	 * @return catalog_ProductdeclinationService
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
		return $this->pp->createQuery('modules_catalog/productdeclination');
	}
	
	/**
	 * Create a query based on 'modules_catalog/productdeclination' model.
	 * Only documents that are strictly instance of modules_catalog/productdeclination
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/productdeclination', false);
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
	 * @return array('id', 'axe1', 'axe2', 'axe3')
	 */
	public function getIdAndAxesArrayByDeclinedProduct($declinedProduct)
	{
		$axeToSort = $declinedProduct->getShowAxeInList();
		$query = $this->createQuery()
			->add(Restrictions::eq('declinedproduct', $declinedProduct))
			->setProjection(Projections::property('id'), Projections::property('axe1'), Projections::property('axe2'), Projections::property('axe3'));
		if ($axeToSort > 0)
		{
			$query->addOrder(Order::asc('axe1'));
			if ($axeToSort > 1)
			{
				$query->addOrder(Order::asc('axe2'));
				if ($axeToSort > 2)
				{
					$query->addOrder(Order::asc('axe3'));
				}
			}
		}
		$query->addOrder(Order::asc('indexInDeclinedproduct'));
		return $query->find();
	}	
	

	/**
	 * Synchronize properties shelf | brand | complementary | similar | upsell
	 * 		| description | serializedattributes | shippingModeId |
	 * 		| pageTitle | pageDescription | pageKeywords
	 * @param catalog_persistentdocument_productdeclination $declination
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct								
	 */
	protected function synchronizePropertiesByDeclinedProduct($declination, $declinedProduct)
	{
		$syncPropModified = $declinedProduct->getDocumentService()->getSynchronizedPropertiesName();
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
				case 'upsell':
					if (!DocumentHelper::documentArrayEquals($declination->getUpsellArray(), $declinedProduct->getUpsellArray()))
					{
						$declination->setUpsellArray($declinedProduct->getUpsellArray());
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
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
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
	 * @param Integer $parentNodeId
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
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
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
			$this->pp->updateDocument($document);
		}
		
		$declinedProduct->getDocumentService()->declinationAdded($declinedProduct, $document);		
		parent::postInsert($document, $parentNodeId);
	}	
	
	
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preUpdate($document, $parentNodeId)
	{
		$this->populatesAxes($document);
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
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function postSave($document, $parentNodeId = null)
	{	
		parent::postSave($document, $parentNodeId);		
		// Handle stock alerts
		catalog_StockService::getInstance()->handleStockAlert($document);
	}

	/**
	 * @param catalog_persistentdocument_productdeclination $targetProduct
	 * @param string $fieldName
	 * @param catalog_persistentdocument_product $product
	 */
	public function addProductToTargetField($targetProduct, $fieldName, $product)
	{
		parent::addProductToTargetField($targetProduct, $fieldName, $product);
		$declinedProduct = $targetProduct->getDeclinedproduct();
		$declinedProduct->getDocumentService()->addProductToTargetField($declinedProduct, $fieldName, $product, $targetProduct);
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $targetProduct
	 * @param string $fieldName
	 * @param catalog_persistentdocument_product $product
	 */
	public function removeProductFromTargetField($targetProduct, $fieldName, $product)
	{
		parent::removeProductFromTargetField($targetProduct, $fieldName, $product);
		
		$declinedProduct = $targetProduct->getDeclinedproduct();
		$declinedProduct->getDocumentService()->removeProductFromTargetField($declinedProduct, $fieldName, $product, $targetProduct);
	}

	/**
	 * @param catalog_persistentdocument_price[] $prices
	 * @param integer[] $declinationIds
	 */
	public function copyPricesOnDeclinationIds($prices, $declinationIds)
	{
		try 
		{
			$this->tm->beginTransaction();
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
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
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
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $product
	 * @param catalog_persistentdocument_compiledproduct $compiledProduct
	 */
	public function updateCompiledProduct($product, $compiledProduct)
	{
		parent::updateCompiledProduct($product, $compiledProduct);
		$declinationId = intval($product->getId());
		
		$data = catalog_DeclinedproductService::getInstance()->getShowInListInfos($product->getDeclinedProduct());
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
		
		if (count($declinationIds) == 1)
		{
			$compiledProduct->setShowInList(true);
			return;	
		}
		
		$before = true;
		$affected = false;
		$needsCompile = array();
		foreach ($declinationIds as $subDeclinationId) 
		{
			if ($declinationId == $subDeclinationId)
			{
				$before = false;
				if (!$affected && $compiledProduct->getPublicationCode() == 0 && $compiledProduct->getIsAvailable())
				{
					$compiledProduct->setShowInList(true);
					$affected = true;
				}
				else
				{
					$compiledProduct->setShowInList(false);
				}
			}
			else
			{
				$cp = $this->getCompiledDeclinationProduct($compiledProduct->getLang(), 
						$compiledProduct->getTopicId(), $subDeclinationId);
				if ($cp === null) 
				{
					Framework::warn(__METHOD__ . ' No compiled product for :' .$subDeclinationId);
					continue;
				}
				
				if (!$affected && $cp->getPublicationCode() == 0  && $cp->getIsAvailable())
				{
					if (!$cp->getShowInList()) {$needsCompile[] = $subDeclinationId;}
					$affected = true;
				}
				else if ($cp->getShowInList())
				{
					$needsCompile[] = $subDeclinationId;
					$affected = true;
				} 
			}
		}
		
		if (!$affected)
		{
			$compiledProduct->setShowInList(true);
		}
		
		if (count($needsCompile))
		{
			$this->setNeedCompile($needsCompile);
		}
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
	 * @return Boolean
	 * @see catalog_ModuleService::getProductModelsThatMayAppearInCarts()
	 * @see markergas_lib_cMarkergasEditorTagReplacer::preRun()
	 */
	public function mayAppearInCarts()
	{
		return true;
	}
	
	/**
	 * @return Boolean
	 * @see markergas_lib_cMarkergasEditorTagReplacer::preRun()
	 */
	public function getPropertyNamesForMarkergas()
	{
		return array(
			'catalog/productdeclination' => array(
				'label' => 'label',
				'codeReference' => 'codeReference'
			),
			'catalog/declinedproduct' => array(
				'label' => 'relatedDeclinedProduct/label',
				'brand' => 'relatedDeclinedProduct/brand/label',
				'codeReference' => 'relatedDeclinedProduct/codeReference'
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
}