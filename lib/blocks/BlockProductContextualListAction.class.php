<?php
/**
 * catalog_BlockProductContextualListAction
 * @package modules.catalog
 */
class catalog_BlockProductContextualListAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @var string[]
	 */
	public static $sortOptions = array('displayMode', 'nbresultsperpage', 'onlydiscount', 
		'onlyavailable', 'priceorder', 'ratingaverageorder', 'brandorder');
		
	/**
	 * @param f_mvc_Request $request
	 * @return integer[] or null
	 */
	protected function getProductIdArray($request)
	{
		// Prepare display configuration.
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$displayConfig = $this->getDisplayConfig($shop);
		
		// Handle the sort configuration.
		if ($displayConfig['showSortMenu'])
		{
			$this->persistSortOptions($request);
		}
		$masterQuery = catalog_ProductService::getInstance()->createQuery();
		
		$query = $masterQuery->createCriteria('compiledproduct')
			->add(Restrictions::published())
			->add(Restrictions::eq('topicId', $this->getContext()->getNearestContainerId()))
			->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()))
			->add(Restrictions::eq('showInList', true));

		$priceorder = $this->findParameterValue('priceorder');
		if ($priceorder == 1)
		{
			$masterQuery->addOrder(Order::asc('compiledproduct.price'));
		}
		else if ($priceorder == 2)
		{
			$masterQuery->addOrder(Order::desc('compiledproduct.price'));
		}
		$ratingaverageorder = $this->findParameterValue('ratingaverageorder');
		if ($ratingaverageorder == 1)
		{
			$masterQuery->addOrder(Order::asc('compiledproduct.ratingAverage'));
		}
		else if ($ratingaverageorder == 2)
		{
			$masterQuery->addOrder(Order::desc('compiledproduct.ratingAverage'));
		}
		$brandorder = $this->findParameterValue('brandorder');
		if ($brandorder == 1)
		{
			$masterQuery->addOrder(Order::asc('compiledproduct.brandLabel'));
		}
		else if ($brandorder == 2)
		{
			$masterQuery->addOrder(Order::desc('compiledproduct.brandLabel'));
		}
		$masterQuery->addOrder(Order::asc('compiledproduct.position'));

		$onlydiscount = $this->findParameterValue('onlydiscount');
		if ($onlydiscount == 'true')
		{
			$query->add(Restrictions::eq('isDiscount', true));
			$this->hideIfNoProduct = false;
		}
		$onlyavailable = $this->findParameterValue('onlyavailable');
		if ($onlyavailable == 'true')
		{
			$query->add(Restrictions::eq('isAvailable', true));
			$this->hideIfNoProduct = false;
		}
		
		$masterQuery->setProjection(Projections::property('id'));
		return $masterQuery->findColumn('id');
	}
	
	/**
	 * @param f_mvc_Request $request
	 */
	protected function persistSortOptions($request)
	{
		$topicId = $this->getPage()->getNearestContainerId();
		$sessionKey = "ProductContextualListSortOptions";
		if (!isset($_SESSION[$sessionKey]))
		{
			$_SESSION[$sessionKey] = array();
		}
		if (!isset($_SESSION[$sessionKey][$topicId]))
		{
			// we changed topic: reset filter info
			if (count($_SESSION[$sessionKey]) > 0)
			{
				$_SESSION[$sessionKey] = array();
			}
			$_SESSION[$sessionKey][$topicId] = array();
		}
		// Get selected values.
		$valueSortOption = array();
		if ($request->hasParameter('updateSortOptions'))
		{
			foreach (self::$sortOptions as $sortOptionName)
			{
				if ($request->hasParameter($sortOptionName))
				{
					$paramValue = $request->getParameter($sortOptionName);
					$_SESSION[$sessionKey][$topicId][$sortOptionName] = $paramValue;
					$valueSortOption[$sortOptionName . $paramValue] = true;
					$request->setAttribute($sortOptionName, $paramValue);
				}
				else
				{
					$_SESSION[$sessionKey][$topicId][$sortOptionName] = null;
					$request->setAttribute($sortOptionName, "");
				}
			}
		}
		else
		{
			foreach (self::$sortOptions as $sortOptionName)
			{
				if (array_key_exists($sortOptionName, $_SESSION[$sessionKey][$topicId]))
				{
					$paramValue = $_SESSION[$sessionKey][$topicId][$sortOptionName];
					$request->setAttribute($sortOptionName, $paramValue);
					$valueSortOption[$sortOptionName . $paramValue] = true;
				}
				else
				{
					$request->setAttribute($sortOptionName, "");
				}
			}
		}
		$request->setAttribute('valueSortOption', $valueSortOption);		
	}
		
	/**
	 * @return string
	 */
	protected function getBlockTitle()
	{
		$title = $this->getConfigurationValue('blockTitle', null);
		if ($title)
		{
			return f_util_HtmlUtils::textToHtml($title);
		}
		$shelf = $this->getCurrentShelf();
		if ($shelf !== null)
		{
			return $shelf->getLabelAsHtml();
		}
		return null;
	}
	
	/**
	 * @return array<string, string>
	 */
	public function getMetas()
	{
		$container = $this->getCurrentShelf();
		$referencingService = catalog_ReferencingService::getInstance();
		if ($container instanceof catalog_persistentdocument_shelf)
		{
			return array("title" => $referencingService->getPageTitleByShelf($container), 
				"description" => $referencingService->getPageDescriptionByShelf($container), 
				"keywords" => $referencingService->getPageKeywordsByShelf($container));
		}
		return array();
	}
	
	/**
	 * @var boolean
	 */
	private $hideIfNoProduct = true;
	
	/**
	 * @return boolean
	 */
	protected function getShowIfNoProduct()
	{
		return !$this->hideIfNoProduct && $this->getCurrentShelf() !== null;
	}
	
	/**
	 * @var catalog_persistentdocument_shelf
	 */
	private $container = null;
	
	/**
	 * @return catalog_persistentdocument_shelf
	 */
	private function getCurrentShelf()
	{
		if ($this->container === null)
		{
			$context = $this->getPage();
			$css = catalog_ShelfService::getInstance();
			$this->container = $css->getByTopic(DocumentHelper::getDocumentInstance($context->getNearestContainerId()));
		}
		return $this->container;
	}
	
	// Deprecated.
	
	/**
	 * @deprecated use getProductIdArray()
	 */
	protected function getProductArray($request, $idsOnly = false)
	{
		// Prepare display configuration.
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$displayConfig = $this->getDisplayConfig($shop);
	
		// Handle the sort configuration.
		if ($displayConfig['showSortMenu'])
		{
			$this->persistSortOptions($request);
		}
		$masterQuery = catalog_ProductService::getInstance()->createQuery();
	
		$query = $masterQuery->createCriteria('compiledproduct')
		->add(Restrictions::published())
		->add(Restrictions::eq('topicId', $this->getContext()->getNearestContainerId()))
		->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()))
		->add(Restrictions::eq('showInList', true));
	
		$priceorder = $this->findParameterValue('priceorder');
		if ($priceorder == 1)
		{
			$masterQuery->addOrder(Order::asc('compiledproduct.price'));
		}
		else if ($priceorder == 2)
		{
			$masterQuery->addOrder(Order::desc('compiledproduct.price'));
		}
		$ratingaverageorder = $this->findParameterValue('ratingaverageorder');
		if ($ratingaverageorder == 1)
		{
			$masterQuery->addOrder(Order::asc('compiledproduct.ratingAverage'));
		}
		else if ($ratingaverageorder == 2)
		{
			$masterQuery->addOrder(Order::desc('compiledproduct.ratingAverage'));
		}
		$brandorder = $this->findParameterValue('brandorder');
		if ($brandorder == 1)
		{
			$masterQuery->addOrder(Order::asc('compiledproduct.brandLabel'));
		}
		else if ($brandorder == 2)
		{
			$masterQuery->addOrder(Order::desc('compiledproduct.brandLabel'));
		}
		$masterQuery->addOrder(Order::asc('compiledproduct.position'));
	
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
	
		if ($idsOnly)
		{
			$masterQuery->setProjection(Projections::property('id'));
			$products = $masterQuery->findColumn('id');
		}
		else
		{
			$products = $masterQuery->find();
		}
	
		if (count($products) == 0 && $hideBlocIfEmpty)
		{
			return null;
		}
		return $products;
	}
}