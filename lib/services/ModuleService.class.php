<?php
/**
 * @package modules.catalog.lib.services
 */
class catalog_ModuleService extends ModuleBaseService
{
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
			return $document->getRelatedDeclinedProduct();
		}
		else if ($document instanceof catalog_persistentdocument_product)
		{
			return $document->getShelf(0);
		}
		return null;
	}
	
	/**
	 * @return Boolean
	 */
	public function areCommentsEnabled()
	{
		$ms = ModuleService::getInstance();
		if (!$ms->moduleExists('comment'))
		{
			return false;
		}
		$currentShop = catalog_ShopService::getInstance()->getCurrentShop();
		if (!$currentShop)
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
	 * @return Boolean
	 */
	public function isSimilarSynchroEnabled()
	{
		return ModuleService::getInstance()->getPreferenceValue('catalog', 'synchronizeSimilar');
	}
	
	/**
	 * @return String[]
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
				$models[] = $model;
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
		
		$maxCount = intval(Framework::getConfigurationValue('modules/catalog/productList'.ucfirst($listName).'MaxCount'));
		$maxCount = ($maxCount < 1) ? null : $maxCount;
			
		$class = Framework::getConfigurationValue('modules/catalog/productList'.ucfirst($listName).'Class');
		if (!f_util_ClassUtils::classExists($class))
		{
			throw new Exception('the class "'.$class.'" does not exist!');
		}
		$list = f_util_ClassUtils::callMethodArgs($class, 'getListInstance', array($listName, $maxCount));
		
		// If persistence is disabled, return the "volatile" list.
		if (Framework::getConfigurationValue('modules/catalog/productList'.ucfirst($listName).'Persist') !== 'true')
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
		
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		foreach ($userList->getProductArray() as $product) 
		{
			if (!$product->canBeDisplayed($shop))
			{
				$userList->removeProduct($product);
			}
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
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		if (count($ids) > 0 && $shop !== null)
		{
			$query = catalog_ProductService::getInstance()->createQuery()->add(Restrictions::published())
				->add(Restrictions::in('id', $ids));
			$query->createCriteria('compiledproduct')->add(Restrictions::published())
				->add(Restrictions::eq('shopId', $shop->getId()));
			$products = $query->find();
			
			$declinationQuery = catalog_ProductdeclinationService::getInstance()->createQuery()
				->add(Restrictions::published())
				->add(Restrictions::in('id', $ids));
			$declinationQuery->createCriteria("declinedproduct")->createCriteria('compiledproduct')
				->add(Restrictions::published())
				->add(Restrictions::eq('shopId', $shop->getId()));
			$declinations = $declinationQuery->find();
			
			return array_merge($products, $declinations);
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
	 * @return catalog_persistentdocument_product[]
	 */
	public function getFavoriteProducts()
	{
		return $this->getProductsFromList(catalog_ProductList::FAVORITE);
	}
	
	/**
	 * @deprecated just call getProductList instead
	 */
	public function mergeFavoriteProductWithList($productList)
	{
		$this->getProductList(catalog_ProductList::FAVORITE);
	}
}