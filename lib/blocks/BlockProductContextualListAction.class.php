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
	 * @param boolean $idsOnly
	 * @return catalog_persistentdocument_product[]
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
		
		$container = f_util_ArrayUtils::lastElement($this->getContext()->getAncestorIds());
		$query = $masterQuery->createCriteria('compiledproduct')
			->add(Restrictions::published())
			->add(Restrictions::eq('topicId', $container))
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
		$request->setAttribute('blockTitle', $this->getBlockTitle());
		return $products;
	}
		
	/**
	 * @param f_mvc_Response $response
	 * @return integer[] or null
	 */
	protected function getProductIdArray($request)
	{
		return $this->getProductArray($request, true);
	}
	
	protected function persistSortOptions($request)
	{
		$topicId = f_util_ArrayUtils::lastElement($this->getContext()->getAncestorIds());
		$sessionKey = "catalog_ProductContextualListSortOptions";
		$storage = change_Controller::getInstance()->getStorage();
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
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return String
	 */
	protected function getBlockTitle()
	{
		$containerId = f_util_ArrayUtils::lastElement($this->getContext()->getAncestorIds());
		$shelf = catalog_ShelfService::getInstance()->getByTopic(DocumentHelper::getDocumentInstance($containerId));
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
			
			$topicId = f_util_ArrayUtils::lastElement($this->getContext()->getAncestorIds());
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