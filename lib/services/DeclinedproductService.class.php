<?php
/**
 * catalog_DeclinedproductService
 * @package catalog
 */
class catalog_DeclinedproductService extends f_persistentdocument_DocumentService
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
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		$result = parent::isPublishable($document);
		if ($result && (catalog_ProductdeclinationService::getInstance()->getCountByDeclinedProduct($document) < 1))
		{
			$this->setActivePublicationStatusInfo($document, '&modules.catalog.document.declinedproduct.no-declination;');
			return false;
		}
		return $result;
	}

	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		if (f_util_StringUtils::isEmpty($document->getAxe2()))
		{
			$document->setAxe2(null);
			$document->setAxe3(null);
			if ($document->getShowAxeInList() > 1)
			{
				$document->setShowAxeInList(1);
			}
		}
		else if (f_util_StringUtils::isEmpty($document->getAxe3()))
		{
			$document->setAxe3(null);
			if ($document->getShowAxeInList() > 2)
			{
				$document->setShowAxeInList(2);
			}
		}
	}	
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		//['label', 'visual', 'codeReference', 'stockQuantity']
		$firstDeclination = $document->getDeclination0Infos();
		if (isset($firstDeclination['label']))
		{
			$cpds = catalog_ProductdeclinationService::getInstance();
			$declination = $cpds->getNewDocumentInstance();
			$declination->setLabel($firstDeclination['label']);
			$declination->setDeclinedproduct($document);
			$declination->setCodeReference($firstDeclination['codeReference']);
			if (isset($firstDeclination['visual']))
			{
				$declination->setVisual($firstDeclination['visual']);
			}
			if (isset($firstDeclination['stockQuantity']))
			{
				$declination->setStockQuantity($firstDeclination['stockQuantity']);
			}
			$cpds->save($declination, $document->getId());
		}
	}

	/**
	 * @param catalog_persistentdocument_declinedproduct $targetProduct
	 * @param string $fieldName
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_productdeclination $targetDeclination
	 */
	public function addProductToTargetField($targetProduct, $fieldName, $product, $targetDeclination = null)
	{
		//TODO override this for specific process
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $targetProduct
	 * @param string $fieldName
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_productdeclination $targetDeclination
	 */
	public function removeProductFromTargetField($targetProduct, $fieldName, $product, $targetDeclination = null)
	{
		//TODO override this for specific process
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preUpdate($document, $parentNodeId = null)
	{
		parent::preUpdate($document, $parentNodeId);
		
		$ps = catalog_PriceService::getInstance();
		$synchronizePricesFrom = $document->getSynchronizePricesFrom();
		if ($synchronizePricesFrom !== null)
		{
			$document->clearSynchronizePricesFrom();
			
			// Delete potential existing prices on the declined product.
			$ps->createQuery()->add(Restrictions::eq('productId', $document->getId()))->delete();	
			$prices = $this->movePricesFromDeclination($document, $synchronizePricesFrom);
			
			// Delete existing prices on the declinations, except for the selected one.
			$declinationIds = catalog_ProductdeclinationService::getInstance()->getIdArrayByDeclinedProduct($document);
			$ps->createQuery()->add(Restrictions::in('productId', $declinationIds))->delete();
			
			catalog_ProductdeclinationService::getInstance()->copyPricesOnDeclinationIds($prices, $declinationIds);
		}
		else if ($document->isPropertyModified('synchronizePrices') && $document->getSynchronizePrices() === false)
		{
			// Update declination prices.
			$declinationIds = DocumentHelper::getIdArrayFromDocumentArray($document->getDeclinationArray());
			$query = catalog_LockedpriceService::getInstance()->createQuery()->add(Restrictions::in('productId', $declinationIds));
			foreach ($query->find() as $lockedPrice)
			{
				$lockedForId = $lockedPrice->getLockedFor();
				if (intval($lockedForId) > 0)
				{
					try 
					{
						$lockForDocument = DocumentHelper::getDocumentInstance($lockedForId);
						if ($lockForDocument instanceof catalog_persistentdocument_price)
						{
							$lockForDocument->getDocumentService()->convertToNotReplicated($lockedPrice, $lockForDocument);
						}
					}
					catch (Exception $e)
					{
						//$lockedForId is not a valid document id
						Framework::exception($e);
					}
				}
			}
			
			// Delete existing prices on the declined product.
			$ps->createQuery()->add(Restrictions::eq('productId', $document->getId()))->delete();
		}
		
		$this->synchronizeFields($document);
		
		$propertiesNeedDeclinationUpdate = array_merge($this->getSynchronizedPropertiesName(), $this->getAxeManagmentPropertiesName());
		$array = array_intersect($document->getModifiedPropertyNames(), $propertiesNeedDeclinationUpdate);
		if (count($array))
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . " Synchronize properties :" . implode(', ', $array));
			}
			$this->touchAllDeclinations($document);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 */
	protected function preDelete($document)
	{
		// Delete the declinations.
		catalog_ProductdeclinationService::getInstance()->createQuery()->add(Restrictions::eq('declinedproduct', $document))->delete();
	}
	
	/**
	 * WARN: This method does not do anything anymore
	 * The job is now done in ProductdeclinationService::synchronizePropertiesByDeclinedProduct().
	 * Please implement getSynchronizedPropertiesName() if you want to deactivate
	 * or add some synchronization between declinedproduct and productdeclination.
	 * Cf. http://www.rbschange.fr/tickets-42692.html
	 * @deprecated will be removed in 4.0
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @return boolean
	 */
	protected function synchronizeFields($document)
	{
		// Nothing there: if similar or complementary modified, this will be done by
		// declination service because of "touchAllDeclinations" called in preUpdate()
		
		return false;
	}
	
	/**
	 * WARN: is not called anymore
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param String $fieldName
	 * @deprecated will be removed in 4.0
	 */
	protected function synchronizeField($document, $fieldName)
	{
		// only there to preserve potential project specific code
		$ucfirstFieldName = ucfirst($fieldName);
		$oldIds = $document->{'get'.$ucfirstFieldName.'OldValueIds'}();
		$currentProducts = $document->{'get'.$ucfirstFieldName.'Array'}();
		$currentIds = DocumentHelper::getIdArrayFromDocumentArray($currentProducts);		
		$declinations = catalog_ProductdeclinationService::getInstance()->getArrayByDeclinedProduct($document);
		$addIds = array_diff($currentIds, $oldIds);
		$removeIds = array_diff($oldIds, $currentIds);
		foreach ($declinations as $declination)
		{
			if (count($addIds))
			{
				// Update added products.
				foreach ($addIds as $productId)
				{
					$product = DocumentHelper::getDocumentInstance($productId);
					if ($declination === $product) {continue;}
					$declination->{'add'.$ucfirstFieldName}($product);
				}
			}
			if (count($removeIds))
			{
				// Update removed products.
				foreach ($removeIds as $productId)
				{
					$product = DocumentHelper::getDocumentInstance($productId);
					if ($declination === $product) {continue;}
					$declination->{'remove'.$ucfirstFieldName}($product);
				}
			}
			$declination->save();
		}
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 * @param catalog_persistentdocument_productdeclination $productDeclination
	 */
	public function declinationAdded($declinedProduct, $productDeclination)
	{
		if ($declinedProduct->isContextLangAvailable() && $declinedProduct->getPublicationstatus() === f_persistentdocument_PersistentDocument::STATUS_ACTIVE && 
			count($declinedProduct->getDeclination0Infos()) === 0)
		{
			//Declination added by code
			$this->publishDocument($declinedProduct, array('cause' => 'declinationInserted', 'declination' => $productDeclination));
		}
	}
		
	/**
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 */
	protected function touchAllDeclinations($declinedProduct)
	{
		$cpds = catalog_ProductdeclinationService::getInstance();
		$declinations = $cpds->getArrayByDeclinedProduct($declinedProduct);
		foreach ($declinations as $declination) 
		{
			if ($declination->isContextLangAvailable())
			{
				$declination->setModificationdate(null);
				$declination->save();
			}
			else if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' declination not available in lang: ' . $declination->getId());
			}
		}
	}

	public function getSynchronizedPropertiesName()
	{
		return array('shelf', 'brand', 'upsell', 'similar', 'complementary',
			'description', 'shippingModeId', 
			'pageTitle', 'pageDescription', 'pageKeywords');
	}
	
	public function getAxeManagmentPropertiesName()
	{
		return array('axe1', 'axe2', 'axe3', 'showAxeInList'); 
	}
	
	private $showInListInfos;
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 * @return integer[][]
	 */	
	public function getShowInListInfos($declinedProduct)
	{
		if ($declinedProduct->getAxesRawConfiguration() === null)
		{
			$data = $this->generateShowInListInfos($declinedProduct);
			$declinedProduct->setAxesRawConfiguration(serialize($data));
		}
		else
		{
			$data = unserialize($declinedProduct->getAxesRawConfiguration());
		}
		return $data;
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 * @return integer[][]
	 */	
	protected function generateShowInListInfos($declinedProduct)
	{
		$rows = catalog_ProductdeclinationService::getInstance()->getIdAndAxesArrayByDeclinedProduct($declinedProduct);
		$axeVisible = $declinedProduct->getShowAxeInList();
		$result = array();
		foreach ($rows as $row) 
		{
			switch ($axeVisible) 
			{
				case 1: $key = $row['axe1'] . '||'; break;
				case 2: $key = $row['axe1'] . '|'.$row['axe2'].'|'; break;
				case 3: $key = $row['axe1'] . '|'.$row['axe2'].'|'.$row['axe3']; break;
				default: $key = '||'; break;
			}
			$result[$key][] = intval($row['id']);
		}
		$result = array_values($result);
		return $result;
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
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 * @param catalog_persistentdocument_price $price
	 */
	public function priceAdded($declinedProduct, $price)
	{
		$declinationIds = catalog_ProductdeclinationService::getInstance()->getIdArrayByDeclinedProduct($declinedProduct);
		if (count($declinationIds))
		{
			catalog_ProductdeclinationService::getInstance()->copyPricesOnDeclinationIds(array($price), $declinationIds);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @param catalog_persistentdocument_price $price
	 */
	public function priceRemoved($product, $price)
	{
		Framework::info(__METHOD__);
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param String $oldPublicationStatus
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		if ($document->isPublished() || $oldPublicationStatus === 'PUBLICATED')
		{	
			$declinations = catalog_ProductdeclinationService::getInstance()->getArrayByDeclinedProduct($document);
			foreach ($declinations as $declination) 
			{
				$declination->getDocumentService()->publishDocumentIfPossible($declination);
			}
		}
	}
	
	/**
	 * 
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @return catalog_ProductAxe[]
	 */
	public function getAxes($product)
	{
		$axes = array(catalog_ProductAxe::getInstanceByName($product->getAxe1(), 1));
		if ($product->getAxe2())
		{
			$axes[] = catalog_ProductAxe::getInstanceByName($product->getAxe2(), 2);
			if ($product->getAxe3())
			{
				$axes[] = catalog_ProductAxe::getInstanceByName($product->getAxe3(), 3);
			}
		}
		return $axes;
	}

	/**
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @return catalog_ProductAxe[]
	 */
	public function getAxesInList($product)
	{
		$result = array();
		$n = $product->getShowAxeInList();
		if ($n > 0)
		{
			$result[] = catalog_ProductAxe::getInstanceByName($product->getAxe1(), 1);
			if ($n >= 2)
			{
				$result[] = catalog_ProductAxe::getInstanceByName($product->getAxe2(), 2);
				if ($n >= 3)
				{
					$result[] = catalog_ProductAxe::getInstanceByName($product->getAxe3(), 3);	
				}	
			}
		}
		return $result;
	}
	
	//Back office functionnality
	
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
		$result = catalog_ProductdeclinationService::getInstance()->getArrayByDeclinedProduct($document);
		$totalCount = count($result);
		return $result;
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @return array
	 */	
	public function getDeclinationsInfos($product)
	{
		$data = array('id' => $product->getId(), 'lang' => $product->getLang(), 'documentversion' => $product->getDocumentversion());
		
		$declinations = $product->getDeclinationArray();
		$data['totalCount'] = count($declinations);
		$data['count'] = count($declinations);
		$data['offset'] = 0;
		$data['nodes'] = array();
		$lang = RequestContext::getInstance()->getLang();
		$stSrv = catalog_StockService::getInstance();
		foreach ($declinations as $declination)
		{
			$langAvailable = $declination->getI18nInfo()->isLangAvailable($lang);
			$data['nodes'][] = array(
				'id' => $declination->getId(),
				'langAvailable' => $langAvailable,
				'label' => ($langAvailable ? $declination->getLabel() : ($declination->getVoLabel() . ' [' . f_Locale::translateUI('&modules.uixul.bo.languages.' . ucfirst($declination->getLang()) . ';') . ']')),
				'codeReference' => $declination->getCodeReference(),
				'stockQuantity' => $stSrv->getStockableDocument($declination)->getCurrentStockQuantity(),
				'stockLevel' => $declination->getAvailability()
			);
		}
		
		return $data;
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 * @param integer $shopId
	 */
	public function hasDeclinationInShopId($declinedProduct, $shopId)
	{
		$delinationIds = catalog_ProductdeclinationService::getInstance()->getIdArrayByDeclinedProduct($declinedProduct);
		if (count($delinationIds))
		{
			return catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::in('product.id', $delinationIds))	
			->add(Restrictions::eq('shopId', $shopId))
			->setProjection(Projections::rowCount('count'))
			->setFetchMode('count')
			->findUnique() > 0;
		}
		return false;
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
			
			foreach ($declinations as $index => $declination) 
			{
				if ($declination instanceof catalog_persistentdocument_productdeclination) 
				{
					$declination->setIndexInDeclinedproduct($index);
					$declination->save();
					unset($declinationToDelete[$declination->getId()]);
				}
			}
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
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$data = parent::getResume($document, $forModuleName, $allowedSections);
		
		$ls = LocaleService::getInstance();
		$rc = RequestContext::getInstance();
		$contextlang = $rc->getLang();
		$lang = $document->isLangAvailable($contextlang) ? $contextlang : $document->getLang();
		
		$declinations = catalog_ProductdeclinationService::getInstance()->getArrayByDeclinedProduct($document);
		$declinationIds = array();
		$compiled = true;
		foreach ($declinations as $declination) 
		{
			$declinationIds[] = $declination->getId();
			$compiled = $compiled && $declination->getCompiled();
		}
		
		$data['properties']['compiled'] = $ls->transBO('f.boolean.' . ($compiled ? 'true' : 'false'), array('ucf'));
		
		//TODO EHAU Alert on declination ?
		$data['properties']['alertCount'] = catalog_AlertService::getInstance()->getPublishedCountByProduct($document);
		
		if (count($declinationIds))
		{	
			try 
			{
				$rc->beginI18nWork($lang);		
				$urlData = array();
				
				$query = catalog_CompiledproductService::getInstance()->createQuery()
					->add(Restrictions::eq('lang', $lang))
					->add(Restrictions::eq('primary', true))
					->add(Restrictions::eq('showInList', true));		
				$query->createCriteria('product')
					->add(Restrictions::in('id', $declinationIds));
				
				foreach ($query->find() as $compiledProduct)
				{
					if ($compiledProduct instanceof catalog_persistentdocument_compiledproduct) 
					{
						$shop = $compiledProduct->getShop();
						$website = $compiledProduct->getWebsite();
						$href = website_UrlRewritingService::getInstance()->getDocumentLinkForWebsite($compiledProduct, $website, $lang)->setArgSeparator('&')->getUrl();
						$urlData[] = array(
							'label' => $ls->transBO('m.catalog.bo.doceditor.url-for-website', array('ucf'), array('website' => $website->getLabel())), 
							'href' => $href, 'class' => ($shop->isPublished() && $compiledProduct->isPublished()) ? 'link' : ''
							);
					}
				}
				$data['urlrewriting'] = $urlData;					
				$rc->endI18nWork();
			}
			catch (Exception $e)
			{
				$rc->endI18nWork($e);
			}
		}
		
		return $data;
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $declinedProduct
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_productdeclination[]
	 */
	public function getPublishedDeclinationsInShop($declinedProduct, $shop)
	{
		return catalog_ProductdeclinationService::getInstance()->getPublishedDeclinationsInShop($declinedProduct, $shop);
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
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @param catalog_persistentdocument_shop $shop
	 * @return media_persistentdocument_media
	 */
	public function getDefaultVisual($product, $shop)
	{
		// get visual from the product.
		$visual = $product->getVisual();

		// ... or from shop.
		if ($visual === null && $shop !== null)
		{
			$visual = $shop->getDefaultDetailVisual();
		}

		// ... or from module preferences.
		if ($visual === null)
		{
			$visual = ModuleService::getInstance()->getPreferenceValue('catalog', 'defaultDetailVisual');
		}
		return $visual;
	}
		
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 */
	public function getListVisual($product, $shop)
	{
		// get visual from the product.
		$visual = $product->getVisual();
	
		// ... or from shop
		if ($visual === null && $shop !== null)
		{
			$visual = $shop->getDefaultListVisual();
		}
	
		// ... or from module preferences
		if ($visual === null)
		{
			$visual = ModuleService::getInstance()->getPreferenceValue('catalog', 'defaultListVisual');
		}
		return $visual;
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @param string $actionType
	 * @param array $formProperties
	 */
	public function addFormProperties($product, $propertiesNames, &$formProperties)
	{
		$preferences = ModuleService::getInstance()->getPreferencesDocument('catalog');
		$formProperties['suggestComplementaryFeederClass'] = $preferences->getSuggestComplementaryFeederClass(); 		
		$formProperties['suggestSimilarFeederClass'] = $preferences->getSuggestSimilarFeederClass(); 		
		$formProperties['suggestUpsellFeederClass'] = $preferences->getSuggestUpsellFeederClass(); 		
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @param string $moduleName
	 * @param string $treeType
	 * @param unknown_type $nodeAttributes
	 */
	public function addTreeAttributes($product, $moduleName, $treeType, &$nodeAttributes)
	{
		$detailVisual = $product->getDefaultVisual();
		if ($detailVisual)
		{
			$nodeAttributes['hasPreviewImage'] = true;
			if ($treeType == 'wlist')
			{
				$nodeAttributes['thumbnailsrc'] = MediaHelper::getPublicFormatedUrl($detailVisual, "modules.uixul.backoffice/thumbnaillistitem");			
			}
		}
	}
	
	/**
	 * @param website_UrlRewritingService $urlRewritingService
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param website_persistentdocument_website $website
	 * @param string $lang
	 * @param array $parameters
	 * @return f_web_Link | null
	 */
	public function getWebLink($urlRewritingService, $document, $website, $lang, $parameters)
	{
		$defaultDeclination = $document->getPublishedDefaultDeclinationInShop();
		if ($defaultDeclination instanceof catalog_persistentdocument_productdeclination)
		{
			return $urlRewritingService->getDocumentLinkForWebsite($defaultDeclination, $website, $lang, $parameters);
		}
		return null;
	}
}