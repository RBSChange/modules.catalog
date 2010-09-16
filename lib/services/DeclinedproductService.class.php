<?php
/**
 * catalog_DeclinedproductService
 * @package catalog
 */
class catalog_DeclinedproductService extends catalog_ProductService
{
	/**
	 * @var catalog_DeclinedproductService
	 */
	private static $instance;

	/**
	 * @return catalog_DeclinedproductService
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
	 * @return catalog_persistentdocument_declinedproduct
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/declinedproduct');
	}

	/**
	 * Create a query based on 'modules_catalog/declinedproduct' model.
	 * Return document that are instance of modules_catalog/declinedproduct,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/declinedproduct');
	}
	
	/**
	 * Create a query based on 'modules_catalog/declinedproduct' model.
	 * Only documents that are strictly instance of modules_catalog/declinedproduct
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/declinedproduct', false);
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_productdeclination
	 */
	public function getDefaultDeclination($product, $shop)
	{
		return f_util_ArrayUtils::firstElement($this->getDisplayableDeclinations($product, $shop));
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_productdeclination[]
	 */
	public function getDisplayableDeclinations($product, $shop)
	{
		$result = array();
		$ids = $this->getDisplayableDeclinationIds($product, $shop);
		foreach ($product->getDeclinationArray() as $declination)
		{
			if (in_array($declination->getId(), $ids))
			{
				$result[] = $declination;
			}
		}
		return $result;
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param boolean $forceRefresh
	 * @return integer[]
	 */
	protected function getDisplayableDeclinationIds($product, $shop, $forceRefresh = false)
	{
		$lang = RequestContext::getInstance()->getLang();
		$metaName = 'm.catalog.sortedDisplayableDeclinationIds.' . $shop->getId() . '.' . $lang;
		if ($forceRefresh || !$product->hasMeta($metaName))
		{
			$query = catalog_PriceService::getInstance()->createQuery()
				->add(Restrictions::published())
				->add(Restrictions::eq('shopId', $shop->getId()))
				->setProjection(Projections::property('productId'));
						
			$declinationQuery = $query->createPropertyCriteria('productId', 'modules_catalog/productdeclination')
				->add(Restrictions::published())
				->add(Restrictions::eq('declinedproduct', $product));
			if (!$shop->getDisplayOutOfStock())
			{
				$declinationQuery->add(Restrictions::ne('stockLevel', catalog_StockService::LEVEL_UNAVAILABLE));
			}
			$displayableIds = $query->findColumn('productId');
			
			$sordedIds = DocumentHelper::getIdArrayFromDocumentArray($product->getPublishedDeclinationArray());
			$sortedIds = array_intersect($sordedIds, $displayableIds);
			$product->setMeta($metaName, implode(',', $sortedIds));
			$product->saveMeta();
		}
		
		return explode(',', $product->getMeta($metaName));
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 */
	public function updateCompiledMetas($product, $shop)
	{
		$this->getDisplayableDeclinationIds($product, $shop, true);
	}
	
	/**
	 * @param Integer $productId
	 * @param String $sortOnField
	 * @param String $sortDirection
	 */
	public function getSortedDeclinationsById($productId, $sortOnField = 'document_label', $sortDirection = 'asc')
	{
		$query = catalog_ProductdeclinationService::getInstance()->createQuery()
			->add(Restrictions::eq('declinedproduct.id', $productId));
		if ($sortDirection == 'desc')
		{
			$query->addOrder(Order::idesc($sortOnField));
		}
		else
		{
			$query->addOrder(Order::iasc($sortOnField));
		}
		return $query->find();
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param Double $quantity
	 * @param Array<String, Mixed> $properties
	 * @return catalog_persistentdocument_product
	 * @see order_CartService::addProduct()
	 */
	public function getProductToAddToCart($product, $shop, $quantity, &$properties)
	{
		parent::getProductToAddToCart($product, $shop, $quantity, $properties);
		return $this->getDefaultDeclination($product, $shop);
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		parent::preSave($document, $parentNodeId);

		// Synchronize shelf property with declinations.
		if ($document->isPropertyModified('shelf'))
		{
			foreach ($document->getDeclinationArray() as $declination)
			{
				$declination->setShelfArray($document->getShelfArray());
			}
		}
		
		// Label for url equals label by default.
		$label = $document->getLabelForUrl();
		if (is_null($label) || strlen($label) == 0)
		{
			$document->setLabelForUrl($document->getLabel());
		}
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postUpdate($document, $parentNodeId = null)
	{
		parent::postUpdate($document, $parentNodeId);
		
		$ps = catalog_PriceService::getInstance();
		$synchronizePricesFrom = $document->getSynchronizePricesFrom();
		if ($synchronizePricesFrom !== null)
		{
			$document->clearSynchronizePricesFrom();
			
			// Delete potential existing prices on the declined product.
			$ps->createQuery()->add(Restrictions::eq('productId', $document->getId()))->delete();
			
			$prices = $this->movePricesFromDeclination($document, $synchronizePricesFrom);
			
			// Delete existing prices on the declinations, except for the selected one.
			$declinationIds = DocumentHelper::getIdArrayFromDocumentArray($document->getDeclinationArray());
			$ps->createQuery()->add(Restrictions::in('productId', $declinationIds))->delete();
			
			catalog_ProductdeclinationService::getInstance()->replicatePricesOnDeclinationIds($prices, $declinationIds);
		}
		else if ($document->isPropertyModified('synchronizePrices') && $document->getSynchronizePrices() === false)
		{
			// Update declination prices.
			$declinationIds = DocumentHelper::getIdArrayFromDocumentArray($document->getDeclinationArray());
			$query = catalog_LockedpriceService::getInstance()->createQuery()->add(Restrictions::in('productId', $declinationIds));
			foreach ($query->find() as $lockedPrice)
			{
				$lockForDocument = DocumentHelper::getDocumentInstance($lockedPrice->getLockedFor());
				$lockForDocument->getDocumentService()->convertToNotReplicated($lockedPrice, $lockForDocument);
			}
			
			// Delete existing prices on the declined product.
			$ps->createQuery()->add(Restrictions::eq('productId', $document->getId()))->delete();
		}
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param integer $declinationId
	 * @return catalog_persistentdocument_price[]
	 */
	protected function movePricesFromDeclination($document, $declinationId)
	{
		$prices = array();
		foreach (catalog_PriceService::getInstance()->createQuery()->add(Restrictions::eq('productId', $declinationId))->find() as $price)
		{
			$price->setProductId($document->getId());
			$price->save();
			$prices[] = $price;
		}
		return $prices;	
	}

	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @return void
	 */
	protected function preDelete($document)
	{
		parent::preDelete($document);	
		$declinations = $document->getDeclinationArray();
		$document->removeAllDeclination();
		$document->setDeclinationsToDelete($declinations);
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 */
	protected function postDelete($document)
	{
		$declinations = $document->getDeclinationsToDelete();
		if (f_util_ArrayUtils::isNotEmpty($declinations))
		{
			foreach ($declinations as $declination)
			{
				if ($declination instanceof catalog_persistentdocument_productdeclination) 
				{
					$declination->getDocumentService()->fullDelete($declination);
				}
			}			
		}
	}

	/**
	 * @return Boolean
	 */
	public function hasIdsForSitemap()
	{
		return true;
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param Integer $maxUrl
	 * @return array
	 */
	public function getIdsForSitemap($website, $maxUrl)
	{
		$shop = catalog_ShopService::getInstance()->getPublishedByWebsite($website);
		if ($shop === null)
		{
			return array();
		}
		$query = $this->createQuery();
		$criteria = $query->createCriteria('compiledproduct')->add(Restrictions::eq('shopId', $shop->getId()))->add(Restrictions::published());
		if (!$website->getLocalizebypath())
		{
			$criteria->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()));
		}
		return $query->setMaxResults($maxUrl)->setProjection(Projections::groupProperty('id', 'id'))->findColumn('id');
	}
		
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		if ($document->getPublishedDeclinationCount() < 1)
		{
			$this->setActivePublicationStatusInfo($document, '&modules.catalog.document.declinedproduct.publication.no-published-declination;');
			return false;
		}
		return parent::isPublishable($document);
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param catalog_persistentdocument_productdeclination[] $declinations
	 */
	public function updateDeclinations($document, $declinations)
	{
		try 
		{
			$this->tm->beginTransaction();
			
			$declinationToDelete = array();
			foreach ($document->getDeclinationArray() as $declination)
			{
				$declinationToDelete[$declination->getId()] = $declination;
			}
			$document->removeAllDeclination();
			foreach ($declinations as $declination) 
			{
				$document->addDeclination($declination);
				unset($declinationToDelete[$declination->getId()]);
			}
			
			$this->save($document);
			foreach ($declinationToDelete as $declination) 
			{
				$declination->delete();
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
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @return array
	 */	
	public function getDeclinationsInfos($product)
	{
		$data = array('id' => $product->getId(), 'lang' => $product->getLang(), 
			'documentversion' => $product->getDocumentversion());
		
		$declinations = $product->getDeclinationArray();
		$data['totalCount'] = count($declinations);
		$data['count'] = count($declinations);
		$data['offset'] = 0;
		$data['nodes'] = array();
		$lang = RequestContext::getInstance()->getLang();
		
		foreach ($declinations as $declination)
		{

			$langAvailable = $declination->getI18nInfo()->isLangAvailable($lang);
			
			$data['nodes'][] = array(
				'id' => $declination->getId(),
				'langAvailable' => $langAvailable,
				'label' => ($langAvailable ? $declination->getLabel() : ($declination->getVoLabel() . ' [' . f_Locale::translateUI('&modules.uixul.bo.languages.' . ucfirst($declination->getLang()) . ';') . ']')),
				'codeReference' => $declination->getCodeReference(),
				'stockQuantity' => $declination->getStockQuantity(),
				'stockLevel' => $declination->getAvailability()
			);
		}
		
		return $data;
	}
		
	/**
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @param catalog_persistentdocument_price $price
	 */
	public function replicatePrice($product, $price)
	{
		$declinationIds = DocumentHelper::getIdArrayFromDocumentArray($product->getDeclinationArray());
		catalog_ProductdeclinationService::getInstance()->replicatePricesOnDeclinationIds(array($price), $declinationIds);
	}
	
	/**
	 * @see catalog_ProductService::getPriceByTargetIds()
	 *
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param integer[] $targetIds
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getPriceByTargetIds($product, $shop, $targetIds, $quantity = 1)
	{
		if ($product->getSynchronizePrices())
		{
			return parent::getPriceByTargetIds($product, $shop, $targetIds, $quantity);
		}
		else
		{
			$defaultDeclination = $product->getDefaultDeclination($shop);
			if ($defaultDeclination !== null)
			{
				return $defaultDeclination->getDocumentService()->getPriceByTargetIds($defaultDeclination, $shop, $targetIds, $quantity);
			}
			return null;
		}
	}
	
	/**
	 * @see catalog_ProductService::getPricesByTargetIds()
	 *
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param integer[] $targetIds
	 * @return catalog_persistentdocument_price
	 */
	public function getPricesByTargetIds($product, $shop, $targetIds)
	{
		if ($product->getSynchronizePrices())
		{
			return parent::getPricesByTargetIds($product, $shop, $targetIds);
		}
		else
		{
			$defaultDeclination = $product->getDefaultDeclination($shop);
			return $defaultDeclination->getDocumentService()->getPricesByTargetIds($defaultDeclination, $shop, $targetIds);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param string[] $subModelNames
	 * @param integer $locateDocumentId null if use startindex
	 * @param integer $pageSize
	 * @param integer $startIndex
	 * @param integer $totalCount
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	public function getVirtualChildrenAt($document, $subModelNames, $locateDocumentId, $pageSize, &$startIndex, &$totalCount)
	{
		$result = $document->getDeclinationArray();
		$totalCount = count($result);
		return $result;
	}	
}