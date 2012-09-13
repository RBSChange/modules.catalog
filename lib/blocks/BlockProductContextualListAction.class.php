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
	 * @return integer
	 */
	protected function getParentId()
	{
		return f_util_ArrayUtils::lastElement($this->getContext()->getAncestorIds());
	}
	
	
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
			->add(Restrictions::eq('topicId', $this->getParentId()))
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
		$topicId = $this->getParentId();
		$sessionKey = "catalog_ProductContextualListSortOptions";
		$storage = $this->getStorage();
		$sortOptions = $storage->read($sessionKey);
		if (!is_array($sortOptions) || $sortOptions['topicId'] != $topicId)
		{
			$sortOptions = array('topicId' => $topicId);
		}
		$updateSession = $request->hasParameter('updateSortOptions');
		
		// Get selected values.
		$valueSortOption = array();
		foreach (self::$sortOptions as $sortOptionName)
		{
			if ($updateSession)
			{
				$paramValue = $request->getParameter($sortOptionName);
			}
			else
			{
				$paramValue = isset($sortOptions[$sortOptionName]) ? $sortOptions[$sortOptionName] : null;
			}
			if ($paramValue !== null)
			{
				$sortOptions[$sortOptionName] = $paramValue;
				$valueSortOption[$sortOptionName . $paramValue] = true;
				$request->setAttribute($sortOptionName, $paramValue);
			}
			else
			{
				unset($sortOptions[$sortOptionName]);
				$request->setAttribute($sortOptionName, "");
			}
		}
		$storage->write($sessionKey, $sortOptions);
		$request->setAttribute('valueSortOption', $valueSortOption);	
	}
	
	/**
	 * @return integer|NULL
	 */
	protected function getDefaultNbresultsperpage()
	{
		$topicId = $this->getParentId();
		$sessionKey = "ProductContextualListSortOptions";
		$storage = $this->getStorage();
		$sortOptions = $storage->read($sessionKey);
		if (is_array($sortOptions) && $sortOptions['nbresultsperpage']);
		{
			$nbresultsperpage = intval($sortOptions['nbresultsperpage']);
			if ($nbresultsperpage > 0)
			{
				return $nbresultsperpage;
			}
		}
		return null;
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
			$css = catalog_ShelfService::getInstance();
			$this->container = $css->getByTopic(DocumentHelper::getDocumentInstance($this->getParentId()));
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
		->add(Restrictions::eq('topicId', $this->getParentId()))
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