<?php
/**
 * catalog_ShopService
 * @package catalog
 */
class catalog_ShopService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_ShopService
	 */
	private static $instance;

	/**
	 * @return catalog_ShopService
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
	 * @return catalog_persistentdocument_shop
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/shop');
	}

	/**
	 * Create a query based on 'modules_catalog/shop' model.
	 * Return document that are instance of modules_catalog/shop,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/shop');
	}
	
	/**
	 * Create a query based on 'modules_catalog/shop' model.
	 * Only documents that are strictly instance of modules_catalog/shop
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/shop', false);
	}
	
	/**
	 * @param website_persistentdocument_systemtopic $topic
	 * @return catalog_persistentdocument_shop
	 */
	public function getByTopic($topic)
	{
		$shop = f_util_ArrayUtils::firstElement($topic->getShopArrayInverse(0, 1));
		if ($shop !== null)
		{
			return $shop;
		}
		return $this->getByDescendant($topic);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return catalog_persistentdocument_shop
	 */
	private function getByDescendant($document)
	{
		$query = $this->createQuery();
		$query->createCriteria('topic')->add(Restrictions::ancestorOf($document->getId()));
		return $query->findUnique();
	}

	private $currentShop = false;
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 */
	public function setCurrentShop($shop)
	{
		$this->currentShop = $shop;
	}
		
	/**
	 * @return catalog_persistentdocument_shop
	 */
	public function getCurrentShop()
	{
		if ($this->currentShop === false)
		{
			$this->currentShop = $this->getDefaultShop();
		}
		return $this->currentShop;
	}
	
	/**
	 * @return catalog_persistentdocument_shop
	 */
	public function getDefaultShop()
	{
		$className = Framework::getConfiguration('modules/catalog/defaultShopStrategyClass', false);
		if ($className !== false)
		{
			$strategy = new $className;
			if ($strategy instanceof catalog_DefaultShopStrategy)
			{
				$shop = $strategy->getShop();
				return $shop === null ? $this->getContextualDefaultShop() : $shop;
			}
		}
		return $this->getContextualDefaultShop();
	}
	
	/**
	 * @return catalog_persistentdocument_shop
	 */
	protected function getContextualDefaultShop()
	{
		$shop = null;
		$pageId = website_WebsiteModuleService::getInstance()->getCurrentPageId();
		if (intval($pageId) > 0)
		{
			$query = $this->createQuery()->add(Restrictions::published());
			$query->createCriteria('topic')->add(Restrictions::ancestorOf($pageId));
			$shop = $query->findUnique();
		}
		
		// or from cart...
		if ($shop === null && catalog_ModuleService::getInstance()->isCartEnabled())
		{		
			$cs = order_CartService::getInstance();
			if ($cs->hasCartInSession())
			{
				$cart = $cs->getDocumentInstanceFromSession();
				if ($cart->getShopId() !== null)
				{
					$shop = $cart->getShop();
				}
			}
		}

		// or default one for website.
		if ($shop === null)
		{
			$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
			if ($website !== null)
			{
				$shop = $this->getDefaultByWebsite($website);
			}
		}	

		return $shop;
	}
	
	/**
	 * @param integer $pageId
	 * @return catalog_persistentdocument_shop
	 */
	private function getShopFromPageId($pageId)
	{
		
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return catalog_persistentdocument_shop[]
	 */
	public function getByProduct($product) 
	{
		$topShelfArray = array();
		foreach ($product->getShelfArray() as $shelf) 
		{
			$topShelf = catalog_TopshelfService::getInstance()->getTopShelfByShelf($shelf);
			$topShelfArray[$topShelf->getId()] = $topShelf;
		}
		if (count($topShelfArray) === 0)
		{
			return array();
		}
		return $this->createQuery()->add(Restrictions::in('topShelf', array_values($topShelfArray)))->find();
	}
	
	/**
	 * @param string $idParamName
	 * @return catalog_persistentdocument_shop
	 */
	public function getShopFromRequest($idParamName)
	{
		if ($idParamName !== null)
		{
			$request = HttpController::getInstance()->getContext()->getRequest();
			if ($request->hasModuleParameter('catalog', $idParamName))
			{
				$shopId = $request->getModuleParameter('catalog', $idParamName);
				return DocumentHelper::getDocumentInstance($shopId, 'modules_catalog/shop');
			}
		}
		return null;
	}
	

	
	/**
	 * @param website_persistentdocument_website $website
	 * @param string $lang
	 */
	public function getDefaultByWebsite($website, $lang = null)
	{
		return $this->getDefaultByWebsiteId($website->getId(), $lang);
	}
	
	private $defaultShopForWebsite = array();
	
	/**
	 * @param integer $websiteId
	 * @param string $lang
	 * @return catalog_persistentdocument_shop
	 */
	public function getDefaultByWebsiteId($websiteId, $lang = null)
	{
		if ($lang === null) { $lang = RequestContext::getInstance()->getLang();}
		if (array_key_exists($websiteId.$lang, $this->defaultShopForWebsite))
		{
			return $this->defaultShopForWebsite[$websiteId.$lang];
		}
		try 
		{
			RequestContext::getInstance()->beginI18nWork($lang);
			$query = catalog_ShopService::getInstance()->createQuery()
				->add(Restrictions::published())
				->add(Restrictions::eq('isDefault', true))
				->add(Restrictions::eq('website.id', $websiteId));
			$shop = $query->findUnique();					
			$this->defaultShopForWebsite[$websiteId.$lang] =  $shop;
			RequestContext::getInstance()->endI18nWork();
		}
		catch (Exception $e)
		{
			RequestContext::getInstance()->endI18nWork($e);
		}
		return $shop;
	}
	
	/**
	 * @param website_persistentdocument_website[] $websites
	 * @return catalog_persistentdocument_shop[]
	 */
	public function getPublishedByWebsites($websites)
	{
		return $this->createQuery()->add(Restrictions::published())->add(Restrictions::in('website', $websites))->find();
	}
	
	/**
	 * @param catalog_persistentdocument_shop $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		// Check parent type.
		$parent = $document->getMountParent();
		if ($parent === null)
		{
			throw new BaseException('Shops must have a mount parent!', 'modules.catalog.document.shop.exception.Mount-parent-required');
		}
		else if (!($parent instanceof website_persistentdocument_topic) && !($parent instanceof website_persistentdocument_website))
		{
			throw new BaseException('Shop parent must be a topic or a website!', 'modules.catalog.document.shop.exception.Mount-parent-bad-type');
		}
		
		// Generate or move the topic.
		$topic = $document->getTopic();
		if ($topic === null)
		{
			$topic = website_SystemtopicService::getInstance()->getNewDocumentInstance();
			$topic->setReferenceId($document->getId());
			$topic->setLabel($document->getLabel());
			$topic->setDescription($document->getDescription());
			$topic->setPublicationstatus('DRAFT');
			$topic->save($parent->getId());
			$document->setTopic($topic);
		}
		else if ($parent !== $this->getParentOf($topic))
		{
			$topic->getDocumentService()->moveTo($topic, $parent->getId());
		}
		
		// Update the website.
		$website = website_WebsiteService::getInstance()->createQuery()
			->add(Restrictions::ancestorOf($topic->getId()))
			->findUnique();
		$document->setWebsite($website);
		
		
		// Refresh topic tree.
		if ($document->isPropertyModified('topShelf'))
		{
			$updatedShelfArray = array();	
			$ss = catalog_ShelfService::getInstance();
			$oldShelvesIds = $document->getTopShelfOldValueIds();
			$newShelvesIds = array();
			
			// Generate topics for added shelves.
			foreach ($document->getTopShelfArray() as $shelf)
			{
				$shelfId = $shelf->getId();
				$newShelvesIds[] = $shelfId;
				if (!in_array($shelfId, $oldShelvesIds))
				{
					$updatedShelfArray[] = $shelfId;
					$ss->addNewTopicToShelfRecursive($shelf, $topic);
				}
			}
			
			TreeService::getInstance()->setTreeNodeCache(false);
			// Remove topics for removed shelves.
			foreach ($oldShelvesIds as $shelfId)
			{
				if (!in_array($shelfId, $newShelvesIds))
				{
					$updatedShelfArray[] = $shelfId;
					$ss->deleteTopicFromShelfRecursive(DocumentHelper::getDocumentInstance($shelfId), $topic);
				}
			}
			
			$document->setUpdatedShelfArray($updatedShelfArray);
		}
	}

	/**
	 * @param catalog_persistentdocument_shop $shop
	 */
	public function setAsDefault($shop)
	{
		if (!$shop->getIsDefault())
		{
			$shop->setIsDefault(true);
			$shop->save();
		}
	}
		
	/**
	 * @param catalog_persistentdocument_shop $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		$topic = $document->getTopic();
		
		$topic->setReferenceId($document->getId());
		$topic->save();
		$topic->activate();
		
		// Generate compiled products.
		catalog_ProductService::getInstance()->setNeedCompileForShop($document);
	}

	/**
	 * @param catalog_persistentdocument_shop $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId)
	{
		// If there is no default shop for this website, set this shop ad default.
		if (!$document->getIsDefault() && $this->getDefaultByWebsite($document->getWebsite()) === null)
		{
			$document->setIsDefault(true);
		}
	}

	/**
	 * @param catalog_persistentdocument_shop $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preUpdate($document, $parentNodeId)
	{
		$newLangs = $document->getNewTranslationLangs();
		if (count($newLangs) > 0)
		{
			catalog_ProductService::getInstance()->setNeedCompileForShop($document);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shop $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postUpdate($document, $parentNodeId)
	{
		// Synchronize topic properties.
		$topic = $document->getTopic();
		$topic->setLabel($document->getLabel());
		$topic->setDescription($document->getDescription());
		$topic->save();
		
		// Generate compiled products.
		if ($document->isPropertyModified('topShelf'))
		{
			$shelfIds = $document->getUpdatedShelfArray();
			if (is_array($shelfIds))
			{
				foreach ($shelfIds as $shelfId) 
				{
					$shelf = catalog_persistentdocument_shelf::getInstanceById($shelfId);
					catalog_ProductService::getInstance()->setNeedCompileForShelf($shelf);
				}
				$document->setUpdatedShelfArray(null);
			}
		}
	}

	/**
	 * @param catalog_persistentdocument_shop $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postSave($document, $parentNodeId)
	{
		// Fix referenceId if set to -1 (when the topic is created in the pre-save).
		$topic = $document->getTopic();
		if ($topic->getReferenceId() === -1)
		{
			$topic->setReferenceId($document->getId());
			$topic->save();
		}
	}

	/**
	 * @param catalog_persistentdocument_shop $document
	 * @return void
	 */
	protected function preDelete($document)
	{
		// Delete the topics related to the shelves.
		$topic = $document->getTopic();
		$ss = catalog_ShelfService::getInstance();
		foreach ($document->getTopShelfArray() as $shelf)
		{
			$ss->deleteTopicFromShelfRecursive($shelf, $topic);
		}
	}

	/**
	 * @param catalog_persistentdocument_shop $document
	 * @return void
	 */
	protected function postDelete($document)
	{
		// Delete compiled products.
		catalog_CompiledproductService::getInstance()->deleteForShop($document);
	}

	/**
	 * @see f_persistentdocument_DocumentService::postDeleteLocalized()
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	protected function postDeleteLocalized($document)
	{
		catalog_ProductService::getInstance()->setNeedCompileForShop($document);
	}
	
	/**
	 * Methode à surcharger pour effectuer des post traitement apres le changement de status du document
	 * utiliser $document->getPublicationstatus() pour retrouver le nouveau status du document.
	 * @param catalog_persistentdocument_shop $document
	 * @param String $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		// Status transit from ACTIVE to PUBLICATED.
		$this->defaultShopForWebsite = array();
		
		if ($document->isPublished())
		{
			$document->getTopic()->activate();
		}
		// Status transit from PUBLICATED to ACTIVE.
		else if ($oldPublicationStatus == 'PUBLICATED')
		{
			$document->getTopic()->deactivate();
		}
		
		if (!isset($params['cause']) || $params["cause"] != "delete")
		{
			if ($document->isPublished() || $oldPublicationStatus == 'PUBLICATED')
			{					
				catalog_ProductService::getInstance()->setNeedCompileForShop($document);
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shop $document
	 * @return website_persistentdocument_page
	 */
	public function getDisplayPage($document)
	{
		$model = $document->getPersistentModel();
		if ($model->hasURL() && $document->isPublished())
		{
			$topic = $document->getTopic();
			if ($topic !== null)
			{
				return $topic->getDocumentService()->getDisplayPage($topic);
			}
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $document
	 * @return integer
	 */
	public function getWebsiteId($document)
	{
		return $document->getWebsite()->getId();
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return Integer
	 */
	protected function getPublishedProductCountByShop($shop)
	{
		$count = catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::eq('shopId', $shop->getId()))
			->add(Restrictions::eq('primary', true))
			->setProjection(Projections::rowCount('count'))->findColumn('count');
		return f_util_ArrayUtils::firstElement($count);
	}
	
	/**
	 * @param catalog_persistentdocument_shop $document
	 * @see framework/persistentdocument/f_persistentdocument_DocumentService::getResume()
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$data = parent::getResume($document, $forModuleName, $allowedSections);
		$data['properties']['website'] = $document->getWebsite()->getLabel();
		$data['properties']['isDefault'] = LocaleService::getInstance()->transBO('f.boolean.' . ($document->getIsDefault() ? 'true' : 'false'));
		$data['properties']['publishedproductcount'] = $this->getPublishedProductCountByShop($document);
		return $data;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $document
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
	public function addTreeAttributes($document, $moduleName, $treeType, &$nodeAttributes)
	{
		$nodeAttributes['topicId'] = $document->getTopic()->getId();
		if ($treeType === 'wlist')
		{
			$nodeAttributes['website'] = $document->getWebsite()->getLabel();
			$nodeAttributes['isDefault'] = LocaleService::getInstance()->transBO('f.boolean.' . ($document->getIsDefault() ? 'true' : 'false'));
		}
	}
		
	// Depreacted
	
	/**
	 * @deprecated
	 */
	public function getCurrencySymbol($shop)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}		
		return $shop->getDefaultBillingArea()->getCurrency()->getSymbol();
	}
	
	/**
	 * @deprecated
	 */
	public function getPriceFormat($shop)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		if ($shop === null) {return "%s";}
		return $shop->getDefaultBillingArea()->getPriceFormat();
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use getDefaultByWebsite
	 */
	public function getPublishedByWebsite($website)
	{
		return $this->getPublishedByWebsiteId($website->getId());
	}
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	private $shopByWebsiteId = array();
	
	/**
	 * @deprecated (will be removed in 4.0) use getDefaultByWebsite
	 */
	public function getPublishedByWebsiteId($websiteId)
	{
		if (!isset($this->shopByWebsiteId[$websiteId]))
		{
			$this->shopByWebsiteId[$websiteId] = $this->createQuery()->add(Restrictions::eq('website.id', $websiteId))
				->add(Restrictions::published())->findUnique();
		}
		return $this->shopByWebsiteId[$websiteId];
	}
}

interface catalog_DefaultShopStrategy 
{
	/**
	 * @return catalog_persistentdocument_shop
	 */
	function getShop();
}