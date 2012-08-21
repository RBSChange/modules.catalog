<?php
/**
 * @package modules.catalog.lib.services
 */
class catalog_ModuleService extends ModuleBaseService
{
	const COMPARISON_LIST = 'catalog_comparison';
	
	/**
	 * Singleton
	 * @var catalog_ModuleService
	 */
	private static $instance = null;

	/**
	 * @return catalog_ModuleService
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
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return f_persistentdocument_PersistentDocument or null
	 */
	public function getVirtualParentForBackoffice($document)
	{
		if ($document instanceof catalog_persistentdocument_price)
		{
			return $document->getProduct();
		}
		else if ($document instanceof catalog_persistentdocument_productdeclination)
		{
			return $document->getDeclinedproduct();
		}
		else if ($document instanceof catalog_persistentdocument_product || $document instanceof catalog_persistentdocument_declinedproduct)
		{
			return $document->getShelf(0);
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return Boolean
	 */
	public function areCommentsEnabled($shop = null)
	{
		$ms = ModuleService::getInstance();
		if (!$ms->moduleExists('comment'))
		{
			return false;
		}
		$currentShop = $shop !== null ? $shop : catalog_ShopService::getInstance()->getCurrentShop();
		if ($currentShop === null)
		{
			return $ms->getPreferenceValue('catalog', 'enableComments');
		}
		return $currentShop->getEnableComments();
	}
	
	/**
	 * @return Boolean
	 */
	public function isCartEnabled()
	{
		return ModuleService::getInstance()->moduleExists('order');
	}
	
	/**
	 * @return Boolean
	 */
	public function areCustomersEnabled()
	{
		return ModuleService::getInstance()->moduleExists('customer');
	}
	
	/**
	 * @return Boolean
	 */
	public function isComplementarySynchroEnabled()
	{
		return ModuleService::getInstance()->getPreferenceValue('catalog', 'synchronizeComplementary');
	}
	
	/**
	 * @return boolean
	 */
	public function isAutoComplementaryEnabled()
	{
		return ModuleService::getInstance()->getPreferenceValue('catalog', 'autoFeedComplementaryMaxCount') != null;
	}
	
	/**
	 * @return Boolean
	 */
	public function isSimilarSynchroEnabled()
	{
		return ModuleService::getInstance()->getPreferenceValue('catalog', 'synchronizeSimilar');
	}
	
	/**
	 * @return boolean
	 */
	public function isAutoSimilarEnabled()
	{
		return ModuleService::getInstance()->getPreferenceValue('catalog', 'autoFeedSimilarMaxCount') != null;
	}
	
	/**
	 * @return boolean
	 */
	public function isAutoUpsellEnabled()
	{
		return ModuleService::getInstance()->getPreferenceValue('catalog', 'autoFeedUpsellMaxCount') != null;
	}
	
	
	
	
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel[]
	 */
	public function getProductModelsThatMayAppearInCarts()
	{
		$model = f_persistentdocument_PersistentDocumentModel::getInstance('catalog', 'product');
		$models = array();
		foreach ($model->getChildrenNames() as $model)
		{
			$modelInstance = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($model);
			if ($modelInstance->getDocumentService()->mayAppearInCarts())
			{
				$models[] = $modelInstance;
			}
		}
		return $models;
	}
	
	/**
	 * @param f_peristentdocument_PersistentDocument $container
	 * @param array $attributes
	 * @param string $script
	 * @return array
	 */
	public function getStructureInitializationAttributes($container, $attributes, $script)
	{
		// Check container.
		if (!$container instanceof catalog_persistentdocument_shop)
		{
			throw new BaseException('Invalid shop', 'modules.catalog.bo.actions.Invalid-shop');
		}
		
		$node = TreeService::getInstance()->getInstanceByDocument($container->getTopic());
		if (count($node->getChildren('modules_website/page')) > 0)
		{
			throw new BaseException('This shop already contains pages', 'modules.catalog.bo.actions.Shop-already-contains-pages');
		}
		
		// Set atrtibutes.
		$attributes['byDocumentId'] = $container->getTopic()->getId();
		return $attributes;
	}
	
	/** Product list handling */
	
	/**
	 * @var catalog_ProductList[]
	 */
	protected $instances = array();
	
	/**
	 * @param string $listName
	 * @return catalog_ProductList
	 */
	public function getProductList($listName, $ignoreCache = false)
	{
		if (!$ignoreCache && isset($this->instances[$listName]))
		{
			return $this->instances[$listName];
		}
		
		if (strpos($listName, '_') !== false)
		{
			list($module, $name) = explode('_', $listName);
		}
		else
		{
			$module = 'catalog';
			$name = $listName;
		}		
		
		$maxCount = intval(Framework::getConfigurationValue('modules/'.$module.'/productList'.ucfirst($name).'MaxCount'));
		$maxCount = ($maxCount < 1) ? null : $maxCount;
			
		$class = Framework::getConfigurationValue('modules/'.$module.'/productList'.ucfirst($name).'Class');
		if (!f_util_ClassUtils::classExists($class))
		{
			throw new Exception('the class "'.$class.'" does not exist!');
		}
		$list = f_util_ClassUtils::callMethodArgs($class, 'getListInstance', array($listName, $maxCount));
		
		// If persistence is disabled, return the "volatile" list.
		if (Framework::getConfigurationValue('modules/'.$module.'/productList'.ucfirst($name).'Persist') !== 'true')
		{
			$this->instances[$listName] = $list;
			return $this->instances[$listName];
		}
		
		// If there is no current user, return the "volatile" list.
		$user = users_UserService::getInstance()->getCurrentFrontEndUser();
		if ($user === null)
		{
			$this->instances[$listName] = $list;
			return $this->instances[$listName];
		}

		// Else, merge it with the persisted one.
		$userList = catalog_ProductlistService::getInstance()->getCurrentProductList($listName, $maxCount);
		foreach ($this->convertIdsToProductArray($list->getProductIds()) as $product) 
		{
			$userList->addProduct($product);
		}
		$userList->saveList();
		
		$this->instances[$listName] = $userList;
		return $this->instances[$listName];
	}
	
	/**
	 * @param integer[] $ids
	 * @return catalog_persistentdocument_product[]
	 */
	protected function convertIdsToProductArray($ids)
	{
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		if (count($ids) > 0 && $website !== null)
		{
			$query = catalog_ProductService::getInstance()->createQuery()->add(Restrictions::published())
				->add(Restrictions::in('id', $ids));
			$query->createCriteria('compiledproduct')->add(Restrictions::published())
				->add(Restrictions::eq('websiteId', $website->getId()));
			return $query->find();
		}
		return array();
	}
	
	/**
	 * @param integer $productId
	 * @return boolean
	 */
	public function addProductIdToList($listName, $productId, $maxCount = null)
	{
		$list = $this->getProductList($listName);
		if ($this->getProductList($listName)->addProductId($productId, $maxCount))
		{
			$list->saveList();
			return true;
		}
		return false;
	}
	
	/**
	 * @param integer $productId
	 * @return boolean
	 */
	public function removeProductIdFromList($listName, $productId)
	{
		$list = $this->getProductList($listName);
		if ($this->getProductList($listName)->removeProductId($productId))
		{
			$list->saveList();
			return true;
		}
		return false;
	}
	
	/**
	 * @return integer[]
	 */
	public function getProductIdsFromList($listName)
	{
		return $this->getProductList($listName)->getProductIds();
	}	
	
	/**
	 * @return catalog_persistentdocument_product[]
	 */
	public function getProductsFromList($listName)
	{
		return $this->convertIdsToProductArray($this->getProductIdsFromList($listName));
	}
	
	/** Product list handling shortcuts */
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return boolean
	 */
	public function addConsultedProduct($product)
	{
		return $this->addProductIdToList(catalog_ProductList::CONSULTED, $product->getId(), 10);
	}
	
	/**
	 * @return integer[]
	 */
	public function getConsultedProductIds()
	{
		return $this->getProductIdsFromList(catalog_ProductList::CONSULTED);
	}	
	
	/**
	 * @return catalog_persistentdocument_product[]
	 */
	public function getConsultedProducts()
	{
		return $this->getProductsFromList(catalog_ProductList::CONSULTED);
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return boolean
	 */
	public function addFavoriteProduct($product)
	{
		return $this->addProductIdToList(catalog_ProductList::FAVORITE, $product->getId());
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return boolean
	 */
	public function removeFavoriteProduct($product)
	{
		return $this->removeProductIdFromList(catalog_ProductList::FAVORITE, $product->getId());
	}
		
	/**
	 * @return integer[]
	 */
	public function getFavoriteProductIds()
	{
		$productIds = $this->getProductIdsFromList(catalog_ProductList::FAVORITE);
		return catalog_ProductService::getInstance()->filterIdsForDisplay($productIds, catalog_ShopService::getInstance()->getCurrentShop());
	}
	
	/**
	 * @return boolean
	 */
	public function useAsyncWebsiteUpdate()
	{
		return Framework::getConfigurationValue('modules/catalog/useAsyncWebsiteUpdate', 'true') == 'true';
	}
	
	/**
	 * @param integer[] $ids
	 */
	public function addAsyncWebsiteUpdateIds($ids)
	{
		if (is_array($ids) || count($ids) > 0)
		{
			$tm = $this->getTransactionManager();
			try
			{
				$tm->beginTransaction();
				$ids = array_map('intval', $ids);
				$rf = generic_persistentdocument_rootfolder::getInstanceById(ModuleService::getInstance()->getRootFolderId('catalog'));
				$oldIds = $rf->hasMeta('catalog_WebsiteSynchroIds') ? $rf->getMeta('catalog_WebsiteSynchroIds') : array();
				if (is_array($oldIds))
				{
					$rf->setMeta('catalog_WebsiteSynchroIds', array_unique(array_merge($oldIds, $ids)));
				}
				else
				{
					$rf->setMeta('catalog_WebsiteSynchroIds', array_unique($ids));
				}
				$rf->saveMeta();
				
				$tm->commit();
			}
			catch (Exception $e)
			{
				$tm->rollback($e);
				throw $e;
			}
		}
	}
	
	/**
	 * @return integer[]
	 */
	public function resetAsyncWebsiteUpdateIds()
	{
		$oldIds = null;
		$tm = $this->getTransactionManager();
		try
		{
			$tm->beginTransaction();
			$rf = generic_persistentdocument_rootfolder::getInstanceById(ModuleService::getInstance()->getRootFolderId('catalog'));
			if ($rf->hasMeta('catalog_WebsiteSynchroIds'))
			{
				$oldIds = $rf->getMeta('catalog_WebsiteSynchroIds');
				$rf->setMeta('catalog_WebsiteSynchroIds', null);
				$rf->saveMeta();
			}
			$tm->commit();
		} 
		catch (Exception $e) 
		{
			$tm->rollback($e);
			throw $e;
		}
		return is_array($oldIds) ? $oldIds : array();
	}
	
	/**
	 * @deprecated just call getProductList instead
	 */
	public function mergeFavoriteProductWithList($productList)
	{
		$this->getProductList(catalog_ProductList::FAVORITE);
	}
	
	// Deprecated.
	
	/**
	 * @deprecated use getFavoriteProductIds
	 */
	public function getFavoriteProducts()
	{
		return $this->getProductsFromList(catalog_ProductList::FAVORITE);
	}
}