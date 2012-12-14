<?php
/**
 * catalog_ShelfService
 * @package modules.catalog
 */
class catalog_ShelfService extends f_persistentdocument_DocumentService
{
	/**
	 * Singleton
	 * @var catalog_ShelfService
	 */
	private static $instance = null;

	/**
	 * @return catalog_ShelfService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return catalog_persistentdocument_shelf
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/shelf');
	}

	/**
	 * Create a query based on 'modules_catalog/shelf' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/shelf');
	}
	
	/**
	 * Create a query based on 'modules_catalog/shelf' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public final function createShelfQuery()
	{
		return $this->pp->createQuery('modules_catalog/shelf');
	}
	
	private $getShelfAncestorIdsCache = array();
	/**
	 * Warning: this method is cached at PHP process level and not invalidated.
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return Integer[]
	 */
	public function getShelfAncestorIds($shelf)
	{
		if ($shelf instanceof catalog_persistentdocument_topshelf)
		{
			return array();
		}
		$shelfId = $shelf->getId();
		if (!isset($this->getShelfAncestorIdsCache[$shelfId]))
		{
			$this->getShelfAncestorIdsCache[$shelfId] = $this->createQuery()->add(Restrictions::ancestorOf($shelfId))->setProjection(Projections::property("id"))->findColumn("id");
		}
		return $this->getShelfAncestorIdsCache[$shelfId];
	}

	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param String $newLabel
	 */
	public function changeLabel($shelf, $newLabel)
	{
		if ($shelf->getLabel() != $newLabel)
		{
			try
			{
				$this->tm->beginTransaction();
				$shelf->setLabel($newLabel);
				$this->pp->updateDocument($shelf);
				$this->tm->commit();
			}
			catch (Exception $e)
			{
				$this->tm->rollBack($e);
			}
		}
	}

	/**
	 * @param website_persistentdocument_systemtopic $topic
	 * @return catalog_persistentdocument_shelf
	 */
	public function getByTopic($topic)
	{
		if ($topic instanceof website_persistentdocument_systemtopic)
		{
			$reference = $topic->getReference();
			if ($reference instanceof catalog_persistentdocument_shelf)
			{
				return $reference;
			}
		}
		return null;
	}

	/**
	 * Returns the top-level shelf of the current shop for the current page.
	 * @see website_WebsiteModuleService::getCurrentPageId()
	 * @return catalog_persistentdocument_shelf
	 */
	public function getCurrentTopShelf()
	{
		$currentPageId = website_WebsiteModuleService::getInstance()->getCurrentPageId();
		// No current page, so no current top shelf.
		if (!$currentPageId)
		{
			return null;
		}

		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		// If we aren't in a shop, there is no top-shelf.
		if ($shop === null)
		{
			return null;
		}

		$topic = website_SystemtopicService::getInstance()->createQuery()
			->add(Restrictions::childOf($shop->getTopic()->getId()))
			->add(Restrictions::ancestorOf($currentPageId))
			->findUnique();

		// A topic is found, so return the associated shelf.
		return $this->getByTopic($topic);
	}
	
	/**
	 * Returns the top-level shelf of the current shop for the current page.
	 * @see website_WebsiteModuleService::getCurrentPageId()
	 * @return catalog_persistentdocument_shelf
	 */
	public function getCurrentShelf()
	{
		$currentPageId = website_WebsiteModuleService::getInstance()->getCurrentPageId();
		// No current page, so no current shelf.
		if (!$currentPageId)
		{
			return null;
		}

		return $this->getByTopic($this->getParentOfById($currentPageId));
	}

	/**
	 * Returns the top shelf (first level) for a given shelf.
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return catalog_persistentdocument_topshelf
	 */
	public function getTopShelfByShelf($shelf)
	{
		if ($shelf instanceof catalog_persistentdocument_topshelf)
		{
			return $shelf;
		}
		else if ($shelf instanceof catalog_persistentdocument_shelf)
		{
			foreach ($this->getAncestorsOf($shelf) as $ancestor)
			{
				if ($ancestor instanceof catalog_persistentdocument_topshelf)
				{
					return $ancestor;
				}
			}
		}
		throw new Exception(__METHOD__ . ' Invalid parameter shelf');
	}

	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return catalog_persistentdocument_shelf[]
	 */
	public function getSubShelves($shelf)
	{
		if ($this !== catalog_ShelfService::getInstance())
		{
			return catalog_ShelfService::getInstance()->getSubShelves($shelf);
		}
		return $this->createQuery()->add(Restrictions::childOf($shelf->getId()))->find();
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return catalog_persistentdocument_shelf[]
	 */
	public function getPublishedSubShelvesInCurrentShop($shelf)
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		return $this->getPublishedSubShelvesInShop($shelf, $shop);
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_shelf[]
	 */
	public function getPublishedSubShelvesInShop($shelf, $shop)
	{
		$query = website_SystemtopicService::getInstance()->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::descendentOf($shop->getTopic()->getId()));
		$query->createPropertyCriteria('referenceId', 'modules_catalog/shelf')
			->add(Restrictions::childOf($shelf->getId()));
			
		$query->setProjection(Projections::property('referenceId'));
		$subShelvesIds = $query->findColumn('referenceId');
		$this->createShelfQuery()->add(Restrictions::in('id', $subShelvesIds))->find();
		$subShelves = array();
		foreach ($subShelvesIds as $subShelfId)
		{
			$subShelves[] = $this->pp->getDocumentInstance($subShelfId);
		}
		return $subShelves;
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return catalog_persistentdocument_shelf[]
	 */
	public function getPublishedSubShelves($shelf)
	{
		return $this->createShelfQuery()->add(Restrictions::childOf($shelf->getId()))
				->add(Restrictions::published())->find();
	}

	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param catalog_persistentdocument_shop $shop
	 * @param string $lang
	 * @return integer
	 */
	public function getPublishedProductCount($shelf, $shop, $lang = null)
	{
		$topic = $this->getRelatedTopicByShop($shelf, $shop);
		if ($topic !== null)
		{
			$query = catalog_CompiledproductService::getInstance()->createQuery();
			$query->add(Restrictions::published());
			$query->add(Restrictions::eq('topicId', $topic->getId()));
			$query->add(Restrictions::eq('lang', ($lang === null) ? RequestContext::getInstance()->getLang() : $lang));
			return f_util_ArrayUtils::firstElement($query->setProjection(Projections::rowCount('count'))->findColumn('count'));
		}
		return 0;
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return boolean
	 */
	public function isPublishable($shelf)
	{
		$result = parent::isPublishable($shelf);
		if ($result)
		{
			$query = $this->createShelfQuery()->add(Restrictions::published());
			$query->add(Restrictions::childOf($shelf->getId()));
			$query->setProjection(Projections::rowCount('count'));
			$result = $query->findUnique();
			if ($result['count'] > 0) 
			{
				return true;
			}
			$query = catalog_ProductService::getInstance()->createQuery()->add(Restrictions::published());
			$query->add(Restrictions::eq('shelf.id', $shelf->getId()));
			$query->setProjection(Projections::rowCount('count'));
			$result = $query->findUnique();
			if ($result['count'] > 0)
			{
				return true;
			}
			
			$this->setActivePublicationStatusInfo($shelf, '&m.catalog.bo.general.no-published-content;');
			return false;
		}
		return $result;
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param catalog_persistentdocument_product $document
	 */
	public function productPublished($shelf, $document)
	{
		if ($shelf->isContextLangAvailable() && $shelf->getPublicationstatus() === f_persistentdocument_PersistentDocument::STATUS_ACTIVE)
		{
			$shelf->getDocumentService()->publishDocument($shelf, array('cause' => 'productPublished'));
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param catalog_persistentdocument_product $document
	 */
	public function productUnpublished($shelf, $document)
	{
		if ($shelf->isContextLangAvailable() && $shelf->getPublicationstatus() === f_persistentdocument_PersistentDocument::STATUS_PUBLISHED)
		{
			$shelf->getDocumentService()->publishDocument($shelf, array('cause' => 'productUnpublished'));
		}		
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param website_persistentdocument_systemtopic $topicParent
	 */
	public function addNewTopicToShelfRecursive($shelf, $topicParent)
	{
		if (catalog_ModuleService::getInstance()->useAsyncWebsiteUpdate())
		{
			catalog_ModuleService::getInstance()->addAsyncWebsiteUpdateIds(array($shelf->getId()));
		}
		else
		{
			// Add topic to the current shelf.
			$topic = $this->addNewTopicToShelf($shelf, $topicParent);
			$this->getPersistentProvider()->updateDocument($shelf);
			
			// Add topics to descendants recursively.
			foreach ($this->getChildrenOf($shelf) as $child)
			{
				if ($child instanceof catalog_persistentdocument_shelf)
				{
					$this->addNewTopicToShelfRecursive($child, $topic);
				}
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param website_persistentdocument_systemtopic $topicParent
	 */
	public function deleteTopicFromShelfRecursive($shelf, $topicParent)
	{
		if (catalog_ModuleService::getInstance()->useAsyncWebsiteUpdate())
		{
			catalog_ModuleService::getInstance()->addAsyncWebsiteUpdateIds(array($shelf->getId()));
		}
		else
		{
			if ($topicParent === null) { throw new Exception('no topic'); }
			$topic = $this->getRelatedTopicByTopicAncestor($shelf, $topicParent);
			website_WebsiteModuleService::getInstance()->removeIndexPage($topic);
			foreach ($this->getChildrenOf($shelf) as $child)
			{
				if ($child instanceof catalog_persistentdocument_shelf)
				{
					$this->deleteTopicFromShelfRecursive($child, $topic);
				}
			}
	
			$rc = RequestContext::getInstance();		
			foreach (array_reverse($topic->getI18nInfo()->getLangs()) as $lang)
			{
				try 
				{
					$rc->beginI18nWork($lang);
					$topic->delete();
					$rc->endI18nWork();
				}
				catch (Exception $e)
				{
					$rc->endI18nWork($e);
				}	
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param website_persistentdocument_systemtopic $systemtopic
	 */
	public function isSystemtopicPublishable($shelf, $systemtopic)
	{
		$ds = $systemtopic->getDocumentService();
		if (!$shelf->isPublished())
		{
			$this->setActivePublicationStatusInfo($systemtopic, '&modules.catalog.document.shelf.systemtopic-publication.shelf-not-published;');
			return false;
		}
		if (!$ds->hasPublishedPages($systemtopic))
		{
			$this->setActivePublicationStatusInfo($systemtopic, '&modules.catalog.document.shelf.systemtopic-publication.has-no-published-page;');
			return false;
		}
		
		$rows = catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::eq('topicId', $systemtopic->getId()))
			->setProjection(Projections::property('id'))
			->setMaxResults(1)
			->find();
		if (count($rows) === 1 || $systemtopic->getDocumentService()->hasPublishedTopics($systemtopic))
		{
			return true;
		}
		$this->setActivePublicationStatusInfo($systemtopic, '&modules.catalog.document.shelf.systemtopic-publication.no-published-product-or-subshelf;');
		return false;
	}
			
	/**
	 * @param catalog_persistentdocument_shelf $document
	 * @return integer
	 */
	public function getWebsiteId($document)
	{
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$data = parent::getResume($document, $forModuleName, array('properties' => true, 'publication' => true, 'localization' => true, 'history' => true));
		$rc = RequestContext::getInstance();
		$ls = LocaleService::getInstance();
		$contextlang = $rc->getLang();
		$lang = $document->isLangAvailable($contextlang) ? $contextlang : $document->getLang();
		try 
		{
			$rc->beginI18nWork($lang);
			$urlData = array();
			$systemTopics = website_SystemtopicService::getInstance()->getByReferenceId($document->getId());			
			foreach ($systemTopics as $systemTopic)
			{
				$shop = catalog_ShopService::getInstance()->getByTopic($systemTopic);
				$href = website_UrlRewritingService::getInstance()->getDocumentLinkForWebsite($systemTopic, null, $lang)->setArgSeparator('&')->getUrl();
				$urlData[] = array(
					'label' => $ls->transBO('m.catalog.bo.doceditor.url-for-website', array('ucf'), array('website' => $shop->getWebsite()->getLabel())), 
					'href' => $href,
					'class' => $systemTopic->isPublished() ? 'link' : ''
				);
			}
			$data['urlrewriting'] = $urlData;
									
			$rc->endI18nWork();
		}
		catch (Exception $e)
		{
			$rc->endI18nWork($e);
		}
		
		return $data;
	}
	
	// --- Private & protected stuff ---
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 */
	private function getContainingShops($shelf)
	{
		return $this->getTopShelfByShelf($shelf)->getShopArrayInverse();
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		// Update label for URL.
		if ($document->isPropertyModified('label'))
		{
			$document->setLabelForUrl($document->getLabel());
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		// Generate related topics.
		if (catalog_ModuleService::getInstance()->useAsyncWebsiteUpdate())
		{
			catalog_ModuleService::getInstance()->addAsyncWebsiteUpdateIds(array($document->getId()));
		}
		else
		{
			$parent = DocumentHelper::getDocumentInstanceIfExists($parentNodeId);
			if ($parent instanceof catalog_persistentdocument_shelf)
			{
				foreach ($parent->getTopicArray() as $topic)
				{
					$this->addNewTopicToShelf($document, $topic);		
				}
				$this->getPersistentProvider()->updateDocument($document);
			}	
		}
	}
	
	/**
	 * @see f_persistentdocument_DocumentService::preDeleteLocalized()
	 *
	 * @param catalog_persistentdocument_shelf $document
	 */
	protected function preDeleteLocalized($document)
	{
		foreach ($document->getTopicArray() as $topic)
		{
			if ($topic->isContextLangAvailable())
			{
				$topic->delete();
			}
		}
	}	
	
	/**
	 * @param catalog_persistentdocument_shelf $document
	 * @return void
	 */
	protected function postDeleteLocalized($document)
	{
		// Refresh compiled product publication if there is a deleted translation.
		catalog_ProductService::getInstance()->setNeedCompileForShelf($document);
	}

	/**
	 * @param catalog_persistentdocument_shelf $document
	 * @return void
	 */
	protected function postDelete($document)
	{
		// Delete compiled products.
		catalog_CompiledproductService::getInstance()->deleteForShelf($document);
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function postUpdate($document, $parentNodeId)
	{
		// Update related topics.
		if ($document->isPropertyModified('description') || $document->isPropertyModified('label') || $document->isPropertyModified('visual'))
		{
			foreach ($document->getTopicArray() as $topic)
			{
				$topic->setLabel($document->getLabel());
				$topic->setDescription($document->getDescription());
				$topic->setVisual($document->getVisual());
				$topic->save();
			}
		}
		catalog_ProductService::getInstance()->setNeedCompileForShelf($document);
	}

	/**
	 * @param catalog_persistentdocument_shelf $document
	 * @param String $oldPublicationStatus
	 * @param Array<Mixed> $params
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		$parentShelf = $document->getParentShelf();
		if ($parentShelf !== null)
		{
			if ($document->isPublished() && $parentShelf->isContextLangAvailable() && $parentShelf->getPublicationstatus() === f_persistentdocument_PersistentDocument::STATUS_ACTIVE)
			{
				$parentShelf->getDocumentService()->publishDocument($parentShelf, array('cause' => 'shelfPublished'));
			}
			else if ($oldPublicationStatus === 'PUBLICATED' && $parentShelf->isContextLangAvailable() && $parentShelf->isPublished())
			{
				$parentShelf->getDocumentService()->publishDocument($parentShelf, array('cause' => 'shelfUnpublished'));
			}
		}
		if ($document->isPublished() || $oldPublicationStatus === 'PUBLICATED')
		{
			// Handle compilation.
			if (!isset($params['cause']) || $params["cause"] != "delete")
			{
				catalog_ProductService::getInstance()->setNeedCompileForShelf($document);
			}
			
			// Update associated topics visibility.
			foreach ($document->getTopicArray() as $topic)
			{
				website_SystemtopicService::getInstance()->publishIfPossible($topic->getId());
			}
		}
	}
	
	/**
	 * Called before the moveToOperation starts. The method is executed INSIDE a
	 * transaction.
	 *
	 * @param catalog_persistentdocument_shelf $document
	 * @param Integer $destId
	 */
	protected function onMoveToStart($document, $destId)
	{
		if (catalog_ModuleService::getInstance()->useAsyncWebsiteUpdate())
		{
			catalog_ModuleService::getInstance()->addAsyncWebsiteUpdateIds(array($document->getId()));
		}
		else
		{
			$sts = website_SystemtopicService::getInstance();
			
			// Move or delete existing topics.
			foreach ($document->getTopicArray() as $topic)
			{
				/* @var $topic website_persistentdocument_systemtopic */
				$rootTopic = $sts->createQuery()->add(Restrictions::ancestorOf($topic->getId()))
					->add(Restrictions::isNotNull('shop.id'))->findUnique();
				$newParent = $sts->createQuery()->add(Restrictions::descendentOf($rootTopic->getId()))
					->add(Restrictions::eq('shelf.id', $destId))->findUnique();
				
				if ($newParent !== null)
				{
					$topic->getDocumentService()->moveTo($topic, $newParent->getId());
				}
				else
				{
					$this->deleteTopicFromShelfRecursive($document, $sts->getParentOf($topic));
				}
			}
			
			// Create missing topics.
			$destination = DocumentHelper::getDocumentInstance($destId);
			foreach ($destination->getTopicArray() as $topic)
			{
				$query = $sts->createQuery()->add(Restrictions::childOf($topic->getId()))
					->add(Restrictions::eq('shelf.id', $document->getId()));
				if ($query->findUnique() === null)
				{
					$this->addNewTopicToShelfRecursive($document, $topic);
				}
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $document
	 * @param Integer $destId
	 * @return void
	 */
	protected function onDocumentMoved($document, $destId)
	{
		catalog_ProductService::getInstance()->setNeedCompileForShelf($document);
	}
	
	/**
	 * @Warning $shelf is updated during the proccess but not saved.
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param website_persistentdocument_systemtopic $topicParent
	 * @return website_persistentdocument_systemtopic
	 */
	protected function addNewTopicToShelf($shelf, $topicParent)
	{
		$rc = RequestContext::getInstance();
		$topic = null;
		foreach ($shelf->getI18nInfo()->getLangs() as $lang)
		{
			try
			{
				$rc->beginI18nWork($lang);
				
				if ($topic === null)
				{
					$topic = website_SystemtopicService::getInstance()->getNewDocumentInstance();	
					//Fill VO
					$topic->setReferenceId($shelf->getId());
					$topic->setLabel($shelf->getLabel());
					$topic->setDescription($shelf->getDescription());
					$topic->setVisual($shelf->getVisual());
					$topic->save($topicParent->getId());
					
					$shelf->addTopic($topic);
				}
				else
				{
					$topic->setLabel($shelf->getLabel());
					$topic->setDescription($shelf->getDescription());
					$topic->save();
				}
				
				$rc->endI18nWork();
			}
			catch (Exception $e)
			{
				$rc->endI18nWork($e);
			}
		}
		return $topic;
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param website_persistentdocument_systemtopic $topicParent
	 * @return website_persistentdocument_systemtopic
	 */
	private function getRelatedTopicByTopicAncestor($shelf, $topicParent)
	{
		return website_SystemtopicService::getInstance()->createQuery()
			->add(Restrictions::descendentOf($topicParent->getId()))
			->add(Restrictions::eq('referenceId', $shelf->getId()))
			->findUnique();
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param catalog_persistentdocument_shop $shop
	 * @return website_persistentdocument_systemtopic
	 */
	public function getRelatedTopicByShop($shelf, $shop)
	{
		$shopTopic = $shop->getTopic();
		return $this->getRelatedTopicByTopicAncestor($shelf, $shopTopic);
	}

	/**
	 * @param website_UrlRewritingService $urlRewritingService
	 * @param catalog_persistentdocument_shelf $document
	 * @param website_persistentdocument_website $website
	 * @param array $lang
	 * @param array $parameters
	 * @return f_web_Link | null
	 */
	public function getWebLink($urlRewritingService, $document, $website, $lang, $parameters)
	{
		if (website_WebsiteModuleService::getInstance()->getCurrentWebsite() == $website)
		{
			$topic = $document->getContextualSystemTopic();
			if ($topic)
			{
				$this->completeParamters($document, $topic, $lang, $parameters);
			}
		}
		$path = $urlRewritingService->getDocumentDefaultPath($document, $lang);
		return $urlRewritingService->getRewriteLink($website, $lang, $path, $parameters);
	}

	/**
	 * @param website_UrlRewritingService $urlRewritingService
	 * @param catalog_persistentdocument_shelf $document
	 * @param website_persistentdocument_systemtopic $systemtopic
	 * @param string $lang
	 * @param array $parameters
	 * @return f_web_Link | null
	 */
	public function getWebLinkForSystemTopic($urlRewritingService, $document, $systemtopic, $lang, $parameters)
	{
		$website = $this->completeParamters($document, $systemtopic, $lang, $parameters);
		if ($website)
		{
			return $urlRewritingService->getDocumentLinkForWebsite($document, $website, $lang, $parameters);
		}
		return null;
	}

	/**
	 * @param catalog_persistentdocument_shelf $document
	 * @param website_persistentdocument_systemtopic $systemtopic
	 * @param string $lang
	 * @param array $parameters
	 * @return website_persistentdocument_website $website
	 */
	protected function completeParamters($document, $topic, $lang, &$parameters)
	{
		$shop = catalog_ShopService::getInstance()->getByTopic($topic);
		if ($shop)
		{
			$website = $shop->getWebsite();
			$catalogParam = isset($parameters['catalogParam']) ? $parameters['catalogParam'] : array();
			if (!$shop->getIsDefaultForLang($lang))
			{
				$catalogParam['shopId'] = $shop->getId();
			}
			else if (isset($catalogParam['shopId']))
			{
				unset($catalogParam['shopId']);
			}
				
			if (count($catalogParam))
			{
				$parameters['catalogParam'] = $catalogParam;
			}
			else if (isset($parameters['catalogParam']))
			{
				unset($parameters['catalogParam']);
			}
			return $website;
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $document
	 * @return website_persistentdocument_page
	 */
	public function getDisplayPage($document)
	{	
		$model = $document->getPersistentModel();
		if ($document->isPublished())
		{
			$shopService = catalog_ShopService::getInstance();
			$shop = $shopService->getShopFromRequest('shopId');
			if ($shop !== null)
			{
				$shopService->setCurrentShop($shop);
			}
			else 
			{
				$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
				$shop = $shopService->getDefaultByWebsite($website);
			}
			if ($shop === null)
			{
				
				return null;
			}
			
			$topic = $this->getRelatedTopicByShop($document, $shop);
			if ($topic !== null)
			{
				return $topic->getDocumentService()->getDisplayPage($topic);
			}
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $document
	 * @param string $moduleName
	 * @param string $treeType
	 * @param unknown_type $nodeAttributes
	 */
	public function addTreeAttributes($document, $moduleName, $treeType, &$nodeAttributes)
	{
		if ($treeType == 'wlist')
		{
			$detailVisual = $document->getVisual();
			if ($detailVisual)
			{
				$nodeAttributes['thumbnailsrc'] = MediaHelper::getPublicFormatedUrl($detailVisual, "modules.uixul.backoffice/thumbnaillistitem");
			}
		}	
	}
	
	/**
	 * 
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param catalog_persistentdocument_product[] $products
	 */
	public function removeProducts($shelf, $products)
	{
		$tm = $this->getTransactionManager();
		try
		{
			$tm->beginTransaction();
			foreach ($products as $product)
			{
				/* @var $product catalog_persistentdocument_product */
				$product->removeShelf($shelf);
				if ($product->isModified())
				{
					$product->save();
				}
			}
			$tm->commit();
		} 
		catch (Exception $e) 
		{
			$tm->rollback($e);
			throw $e;
		}
	}
	
	/**
	 *
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param catalog_persistentdocument_declinedproduct[] $declinedProducts
	 */
	public function removeDeclinedProducts($shelf, $declinedProducts)
	{
		$tm = $this->getTransactionManager();
		try
		{
			$tm->beginTransaction();
			foreach ($declinedProducts as $product)
			{
				/* @var $product catalog_persistentdocument_declinedproduct */
				$product->removeShelf($shelf);
				if ($product->isModified())
				{
					$product->save();
				}
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollback($e);
			throw $e;
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return integer[integer]
	 */
	protected function getParentTopicIds($shelf)
	{
		$result = array();
		$ts = TreeService::getInstance();
		foreach ($shelf->getTopicArray() as $topic)
		{
			/* @var $topic website_persistentdocument_systemtopic */
			$tn = $ts->getInstanceByDocument($topic);
			$result[$topic->getId()] = $tn->getParentId();
		}
		
		foreach (website_SystemtopicService::getInstance()->getByReferenceId(-$shelf->getId()) as $topic)
		{
			/* @var $topic website_persistentdocument_systemtopic */
			$tn = $ts->getInstanceByDocument($topic);
			$result[$topic->getId()] = $tn->getParentId();
		}
		return $result;
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $document
	 * return integer[]
	 */
	public function websiteSynchroCheck($document)
	{
		$ancestorTopicIds = array();
		$parentShelf = $this->getParentOf($document)	;
		if ($parentShelf instanceof catalog_persistentdocument_shelf)
		{
			foreach ($parentShelf->getTopicArray() as $topic)
			{
				/* @var $topic website_persistentdocument_systemtopic */
				$ancestorTopicIds[] = $topic->getId();
			}
		}
		return $this->topicsCheck($document, $ancestorTopicIds);
	}
		
	/**
	 * 
	 * @param catalog_persistentdocument_shelf $document
	 * @param integer[] $ancestorTopicIds
	 * @return integer[]
	 */
	protected function topicsCheck($document, $ancestorTopicIds)
	{
		$shelfId = $document->getId();
		$result = array();
		$canUpdate = true;
		$subShelfArray = $this->getSubShelves($document);
		$subShelfParentTopicIds = array();
		if (count($subShelfArray))
		{
			foreach ($subShelfArray as $subShelf)
			{
				/* @var $subShelf catalog_persistentdocument_shelf */
				$result[] = $subShelf->getId();
				foreach ($this->getParentTopicIds($subShelf) as $tid => $tpid)
				{
					$subShelfParentTopicIds[$tid] = $tpid;
				}
			}
		}

		$parentTopicIds = $this->getParentTopicIds($document);
		$addParentIds = array_diff($ancestorTopicIds, $parentTopicIds);

		foreach ($addParentIds as $id)
		{
			$topicParent = DocumentHelper::getDocumentInstance($id);
			$this->addNewTopicToShelf($document, $topicParent);
		}
		
		$removeParentIds = array_diff($parentTopicIds, $ancestorTopicIds);
		$topicsToDelete = array();
		foreach ($removeParentIds as $topicId => $parentTopicId)
		{
			$topic = DocumentHelper::getDocumentInstance($topicId);
			$document->removeTopic($topic);
			
			if (in_array($topicId, $subShelfParentTopicIds))
			{
				if ($topic->getReferenceId() == $document->getId())
				{
					$topic->setReferenceId(-$document->getId());
					$topic->save();
				}
				$canUpdate = false;
			}
			else
			{
				$topicsToDelete[] = $topic;
			}
		}
		
		if (count($addParentIds) || count($removeParentIds))
		{
			$this->getPersistentProvider()->updateDocument($document);
		}
		
		foreach ($topicsToDelete as $topic)
		{
			/* @var $topic website_persistentdocument_systemtopic */
			$canDelete = true;
			foreach ($topic->getDocumentService()->getChildrenOf($topic) as $value)
			{
				if (!($value instanceof website_persistentdocument_pagereference))
				{
					$canDelete = false;
					break;
				}
			} 
			
			if ($canDelete)
			{
				website_MenuitemdocumentService::getInstance()->deleteByDocument($topic);
				website_WebsiteModuleService::getInstance()->removeIndexPage($topic);
				$topic->getDocumentService()->purgeDocument($topic);
			}
			else
			{
				$topic->getDocumentService()->transform($topic, 'modules_website/topic');
			}
		}
		
		if ($canUpdate)
		{			
			catalog_ProductService::getInstance()->setNeedCompileForShelf($document, false);
		}
		else
		{
			$result[] = $document->getId();
		}
		return $result;	
	}
}