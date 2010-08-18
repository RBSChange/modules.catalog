<?php
/**
 * catalog_BlockProductContextualListAction
 * @package modules.catalog
 */
class catalog_BlockProductContextualListAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @var String[]
	 */
	public static $sortOptions = array('displayMode', 'nbresultsperpage', 'onlydiscount', 
			'onlyavailable', 'priceorder', 'ratingaverageorder', 'brandorder');
	
	/**
	 * @param f_mvc_Response $response
	 * @return catalog_persistentdocument_product[]
	 */
	protected function getProductArray($request)
	{
		// Prepare display configuration.
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$displayConfig = $this->getDisplayConfig($shop);
		
		// Handle the sort configuration.
		if ($displayConfig['showSortMenu'])
		{
			$this->persistSortOptions($request);
		}
		
		// Get products to display.
		$query = catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::eq('topicId', $this->getPage()->getNearestContainerId()))
			->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()))
			->setProjection(Projections::property('product'))
			->setFetchColumn('product');
		$priceorder = $this->findParameterValue('priceorder');
		if ($priceorder == 1)
		{
			$query->addOrder(Order::asc('price'));
		}
		else if ($priceorder == 2)
		{
			$query->addOrder(Order::desc('price'));
		}
		$ratingaverageorder = $this->findParameterValue('ratingaverageorder');
		if ($ratingaverageorder == 1)
		{
			$query->addOrder(Order::asc('ratingAverage'));
		}
		else if ($ratingaverageorder == 2)
		{
			$query->addOrder(Order::desc('ratingAverage'));
		}
		$brandorder = $this->findParameterValue('brandorder');
		if ($brandorder == 1)
		{
			$query->addOrder(Order::asc('brandLabel'));
		}
		else if ($brandorder == 2)
		{
			$query->addOrder(Order::desc('brandLabel'));
		}
		$query->addOrder(Order::asc('position'));
		
		$hideBlocIfEmpty = true;
		$onlydiscount = $this->findParameterValue('onlydiscount');
		if ($onlydiscount == 'true')
		{
			$query->add(Restrictions::eq('isDiscount', true));
			$hideBlocIfEmpty = false;
		}
		$onlyavailable = $this->findParameterValue('onlyavailable');
		if ($onlyavailable == 'true')
		{
			$query->add(Restrictions::eq('isAvailable', true));
			$hideBlocIfEmpty = false;
		}
		$products = $query->find();
		if (count($products) == 0 && $hideBlocIfEmpty)
		{
			return null;
		}
		
		$request->setAttribute('blockTitle', $this->getBlockTitle());
		return $products;
	}
		
	/**
	 * @param f_mvc_Response $response
	 * @return integer[] or null
	 */
	protected function getProductIdArray($request)
	{

		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$displayConfig = $this->getDisplayConfig($shop);
		
		// Handle the sort configuration.
		if ($displayConfig['showSortMenu'])
		{
			$this->persistSortOptions($request);
		}
		
		$masterQuery = catalog_ProductService::getInstance()
			->createQuery()
			->setProjection(Projections::property('id'));
			
		$query = $masterQuery->createCriteria('compiledproduct')
			->add(Restrictions::published())
			->add(Restrictions::eq('topicId', $this->getPage()->getNearestContainerId()))
			->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()));
			
		$priceorder = $this->findParameterValue('priceorder');
		if ($priceorder == 1)
		{
			$masterQuery->addOrder(Order::asc('compiledproduct.price'));
		}
		elseif ($priceorder == 2)
		{
			$masterQuery->addOrder(Order::desc('compiledproduct.price'));
		}
		
		$ratingaverageorder = $this->findParameterValue('ratingaverageorder');
		if ($ratingaverageorder == 1)
		{
			$masterQuery->addOrder(Order::asc('compiledproduct.ratingAverage'));
		}
		elseif ($ratingaverageorder == 2)
		{
			$masterQuery->addOrder(Order::desc('compiledproduct.ratingAverage'));
		}
		$brandorder = $this->findParameterValue('brandorder');
		if ($brandorder == 1)
		{
			$masterQuery->addOrder(Order::asc('compiledproduct.brandLabel'));
		}
		elseif ($brandorder == 2)
		{
			$masterQuery->addOrder(Order::desc('compiledproduct.brandLabel'));
		}
		
		$hideBlocIfEmpty = true;
		$onlydiscount = $this->findParameterValue('onlydiscount');
		if ($onlydiscount == 'true')
		{
			$query->add(Restrictions::eq('isDiscount', true));
			$hideBlocIfEmpty = false;
		}
		$onlyavailable = $this->findParameterValue('onlyavailable');
		if ($onlyavailable == 'true')
		{
			$query->add(Restrictions::eq('isAvailable', true));
			$hideBlocIfEmpty = false;
		}
		$products = $masterQuery->findColumn('id');
		if (count($products) == 0 && $hideBlocIfEmpty)
		{
			return null;
		}
		
		$request->setAttribute('blockTitle', $this->getBlockTitle());
		return $products;
	}
	
	protected function persistSortOptions($request)
	{
		// Get selected values.
		$valueSortOption = array();
		if ($request->hasParameter('updateSortOptions'))
		{
			foreach (self::$sortOptions as $sortOptionName)
			{
				if ($request->hasParameter($sortOptionName))
				{
					$paramValue = $request->getParameter($sortOptionName);
					$_SESSION[$sortOptionName] = $paramValue;
					$valueSortOption[$sortOptionName . $paramValue] = true;
				}
				else
				{
					$_SESSION[$sortOptionName] = null;
				}
			}
		}
		else
		{
			foreach (self::$sortOptions as $sortOptionName)
			{
				if (array_key_exists($sortOptionName, $_SESSION))
				{
					$paramValue = $_SESSION[$sortOptionName];
					$request->setAttribute($sortOptionName, $paramValue);
					$valueSortOption[$sortOptionName . $paramValue] = true;
				}
			}
		}
		$request->setAttribute('valueSortOption', $valueSortOption);		
	}
		
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return String
	 */
	protected function getBlockTitle()
	{
		$context = $this->getPage();
		$shelf = catalog_ShelfService::getInstance()->getByTopic(
				DocumentHelper::getDocumentInstance($context->getNearestContainerId()));
		if ($shelf !== null)
		{
			return $shelf->getLabelAsHtml();
		}
		return null;
	}
	
	/**
	 * @return array<String, String>
	 */
	public function getMetas()
	{
		$container = $this->getCurrentEcommerceContainer();
		$referencingService = catalog_ReferencingService::getInstance();
		if ($container instanceof catalog_persistentdocument_shelf)
		{
			return array("title" => $referencingService->getPageTitleByShelf($container), 
					"description" => $referencingService->getPageDescriptionByShelf($container), 
					"keywords" => $referencingService->getPageKeywordsByShelf($container));
		}
		else if ($container instanceof catalog_persistentdocument_shop)
		{
			return array("title" => $referencingService->getPageTitleByShop($container), 
					"description" => $referencingService->getPageDescriptionByShop($container), 
					"keywords" => $referencingService->getPageKeywordsByShop($container));
		}
		return null;
	}
	
	private $container = null;
	
	private function getCurrentEcommerceContainer()
	{
		if ($this->container === null)
		{
			$topicId = $this->getContext()
				->getNearestContainerId();
			$topic = DocumentHelper::getDocumentInstance($topicId);
			if ($topic instanceof website_persistentdocument_systemtopic)
			{
				$shelf = DocumentHelper::getDocumentInstance($topic->getReferenceId());
				if ($shelf instanceof catalog_persistentdocument_shelf)
				{
					$this->container = $shelf;
				}
				else
				{
					$this->container = catalog_ShopService::getInstance()->getCurrentShop();
				}
			}
		}
		return $this->container;
	}
}