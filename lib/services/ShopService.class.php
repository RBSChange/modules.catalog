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

	private $currentShop;
	
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
	public function getCurrentShop($useCache = true)
	{
		$shop = null;
		if ($useCache)
		{
			$shop = $this->currentShop;
		}
		if ($shop === null)
		{
			// From page...
			$pageId = website_WebsiteModuleService::getInstance()->getCurrentPageId();
			$shop = $this->getShopFromPageId($pageId);
			
			// or from cart...
			if ($shop === null && catalog_ModuleService::getInstance()->isCartEnabled())
			{
				$cart = order_CartService::getInstance()->getDocumentInstanceFromSession();
				if ($cart !== null && $cart->getShopId() !== null)
				{
					$shop = $cart->getShop();
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
		}
		if ($useCache)
		{
			$this->currentShop = $shop;
		}
		return $shop;
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
	 * @param integer $pageId
	 * @return catalog_persistentdocument_shop
	 */
	private function getShopFromPageId($pageId)
	{
		if ($pageId !== null)
		{
			$page = DocumentHelper::getDocumentInstance($pageId, 'modules_website/page');
			$query = $this->createQuery()->add(Restrictions::published());
			$query->createCriteria('topic')->add(Restrictions::ancestorOf($page->getId()));
			$shop = $query->findUnique();
			if ($shop !== null)
			{
				return $shop;
			}
		}
		return null;
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 */
	public function getDefaultByWebsite($website)
	{
		return $this->getDefaultByWebsiteId($website->getId());
	}
	
	/**
	 * @var catalog_persistentdocument_shop[]
	 */
	private $defaultShopByWebsiteId = array();
	
	/**
	 * @param integer $websiteId
	 */
	public function getDefaultByWebsiteId($websiteId)
	{
		if (!isset($this->defaultShopByWebsiteId[$websiteId]))
		{
			$query = catalog_ShopService::getInstance()->createQuery();
			$query->add(Restrictions::published());
			$query->add(Restrictions::eq('isDefault', true));
			$query->add(Restrictions::eq('website.id', $websiteId));
			$this->defaultShopByWebsiteId[$websiteId] = $query->findUnique();
		}
		return $this->defaultShopByWebsiteId[$websiteId];
	}
	
	/**
	 * @param integer $websiteId
	 */
	private function setDefaultByWebsiteId($websiteId, $shop)
	{
		$this->defaultShopByWebsiteId[$websiteId] = $shop;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return String
	 * @throw catalog_Exception if the currencyCode does not related to an existing currency code
	 */	
	public function getCurrencySymbol($shop)
	{
		$ls = list_StaticlistService::getInstance();
		$list = $ls->getDocumentInstanceByListId('modules_catalog/currencycode');
		$symbol = $ls->getItemLabel($list, $shop->getCurrencyCode());
		if (is_null($symbol))
		{
			throw new catalog_Exception($shop->getCurrencyCode() . " does not relate to an existing curreny code");
		}
		return $symbol;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return String
	 */
	public function getPriceFormat($shop)
	{
		if ($shop === null)
		{
			return "%s";
		}
		$currency = $shop->getCurrencySymbol();
		switch ($shop->getCurrencyPosition())
		{
			case catalog_PriceHelper::CURRENCY_POSITION_LEFT :
				$format = $currency . " %s";
				break;

			case catalog_PriceHelper::CURRENCY_POSITION_RIGHT :
			default :
				$format = "%s ". $currency ;
				break;
		}
		return $format;
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
					$ss->addNewTopicToShelfRecursive($shelf, $topic);
				}
			}
			
			TreeService::getInstance()->setTreeNodeCache(false);
			// Remove topics for removed shelves.
			foreach ($oldShelvesIds as $shelfId)
			{
				if (!in_array($shelfId, $newShelvesIds))
				{
					$ss->deleteTopicFromShelfRecursive(DocumentHelper::getDocumentInstance($shelfId), $topic);
				}
			}
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
			catalog_ProductService::getInstance()->setNeedCompileForShop($document);
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
		
	// Depreacted
	
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