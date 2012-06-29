<?php
/**
 * catalog_BlockProductBaseAction
 * @package modules.catalog
 */
abstract class catalog_BlockProductlistBaseAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @return string
	 */
	protected function getDisplayMode($request)
	{
		if ($request->hasNonEmptyParameter('displayMode'))
		{
			return $request->getParameter('displayMode');
		}
		else
		{
			return $this->getConfigurationValue('displayMode', 'List');
		}
	}
	
	/**
	 * @return boolean
	 */
	protected function displayCustomerPrice()
	{
		return catalog_ModuleService::areCustomersEnabled() && $this->getConfigurationParameter('displayCustomerPrice', 'true') === 'true';
	}
	
	/**
	 * @var array
	 */
	protected $displayConfig;
			
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return array
	 */
	protected function generateDisplayConfig($shop)
	{
		$displayConfig = array();
		
		$displayConfig['blockId'] = $this->getBlockId();
		$displayConfig['showPricesWithTax'] = $this->getShowPricesWithTax($shop);
		$displayConfig['showPricesWithoutTax'] = $this->getShowPricesWithoutTax($shop);
		$displayConfig['showPricesWithAndWithoutTax'] = $displayConfig['showPricesWithTax'] && $displayConfig['showPricesWithoutTax'];
		$displayConfig['showPrices'] = $displayConfig['showPricesWithTax'] || $displayConfig['showPricesWithoutTax'];
		$displayConfig['showShareBlock'] = $this->getShowShareBlock();
		$displayConfig['showRatingAverage'] = catalog_ModuleService::getInstance()->areCommentsEnabled() ? $this->getConfigurationValue('displayratingaverage', false) : false;
		$displayConfig['showProductPictograms'] = $this->getConfigurationValue('displayproductpicto', false);
		$displayConfig['showAnimPictogramBlock'] = $displayConfig['showProductPictograms'] && ModuleService::getInstance()->moduleExists('marketing');
		$displayConfig['showAvailability'] = $shop->getDisplayOutOfStock() && $this->getConfigurationValue('displayavailability', true);
		$displayConfig['showSortMenu'] = $this->getConfigurationValue('displaysortmenu', false);
		$displayConfig['showResultCountSelector'] = $this->getConfigurationValue('displaynbresultsperpage', false);
		$displayConfig['showDiscountFilter'] = $this->getConfigurationValue('displaydiscountfilter', false);
		$displayConfig['showBrandOrder'] = $this->getConfigurationValue('Displaybrandorder', false);
		$displayConfig['controlsmodule'] = 'catalog';
		$displayConfig['controlstemplate'] = 'Catalog-Inc-ProductListOrderOptions';
		
		// @deprecated
		$displayConfig['showQuantitySelector'] = $this->getConfigurationValue('activatequantityselection', true);
		// @depreacted
		$displayConfig['showProductDescription'] = $this->getConfigurationValue('displayproductdescription', false);
		
		$globalButtons = array();
		$displayConfig['showAddToCart'] = $this->getShowAddToCart();
		if ($displayConfig['showAddToCart'])
		{
			$globalButtons[] = $this->getButtonInfo('addToCart', 'add-to-cart');
		}
		$displayConfig['useAddToCartPopin'] = $displayConfig['showAddToCart'] && $this->getConfigurationValue('useAddToCartPopin', true);
		$displayConfig['showAddToFavorite'] = $this->getConfigurationValue('showaddtofavorite', false);
		if ($displayConfig['showAddToFavorite'])
		{
			$globalButtons[] = $this->getButtonInfo('addToFavorite', 'add-to-favorite');
		}
		$displayConfig['showAddToComparison'] = $this->getConfigurationValue('showaddtocomparison', false);
		if ($displayConfig['showAddToComparison'])
		{
			$globalButtons[] = $this->getButtonInfo('addToComparison', 'add-to-comparison');
		}
		$displayConfig['globalButtons'] = $globalButtons;
		$displayConfig['showCheckboxes'] = count($globalButtons) > 0;
		$displayConfig['displayCustomerPrice'] = $this->displayCustomerPrice();
		$displayConfig['showMessages'] = $this->getConfigurationValue('showmessages', true);
		return $displayConfig;		
	}
	
	/**
	 * @param array $displayConfig
	 * @param shop_persistentdocument_shop $shop
	 * @return array
	 */
	protected function generateItemDisplayConfig($displayConfig, $shop)
	{
		return array(
			'showCheckboxes' => $displayConfig['showCheckboxes'],
			'showRatingAverage' => $displayConfig['showRatingAverage'],
			'showAvailability' => $displayConfig['showAvailability'],
			'showPricesWithoutTax' => $displayConfig['showPricesWithoutTax'],
			'showPricesWithTax' => $displayConfig['showPricesWithTax'],
			'showAddToCart' => $displayConfig['showAddToCart'],
			'showQuantitySelector' => $displayConfig['showQuantitySelector'],
			'displayCustomerPrice' => $displayConfig['displayCustomerPrice'],
			'showProductDescription' => $displayConfig['showProductDescription'],
			'showProductPictograms' => $displayConfig['showProductPictograms'],
			'showAnimPictogramBlock' => $displayConfig['showAnimPictogramBlock'],
			'useAddToCartPopin' => $displayConfig['useAddToCartPopin'],
		);
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return array
	 */
	protected function getDisplayConfig($shop)
	{
		if ($this->displayConfig === null)
		{
			$this->displayConfig = $this->generateDisplayConfig($shop);
		}
		return $this->displayConfig;
	}
	

	
	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	protected function getConfigurationValue($key, $default = null)
	{
		$configuration = $this->getConfiguration();
		$getter = 'get' . ucfirst($key);
		if (f_util_ClassUtils::methodExists($configuration, $getter))
		{
			return f_util_ClassUtils::callMethodOn($configuration, $getter);
		}
		return $default;
	}
	
	/**
	 * @param string $name
	 * @param string $rootKey
	 * @return array
	 */
	protected function getButtonInfo($name, $rootKey)
	{
		$ls = LocaleService::getInstance();
		return array(
			'name' => $name,
			'rootKey' => $rootKey, 
			'label' => $ls->trans('m.catalog.frontoffice.' . $rootKey . '-button', array('ucf', 'html')),
			'help' => $ls->trans('m.catalog.frontoffice.' . $rootKey . '-help', array('ucf', 'html'))
		);
	}
	
	/**
	 * @param block_BlockRequest $request
	 * @return string the display mode
	 */
	protected function getMaxresults($request)
	{
		$nbresultsperpage = $this->findParameterValue('nbresultsperpage');
		if ($nbresultsperpage > 0)
		{
			return $nbresultsperpage;
		}
		return 12;
	}
	
	/**
	 * @param catalog_persistentdocument_shop getShowPricesWithTax
	 * @return boolean
	 */
	protected function getShowPricesWithTax($shop)
	{
		// TODO: add block preferences.
		return $shop->getDisplayPriceWithTax();
	}
	
	/**
	 * @param catalog_persistentdocument_shop getShowPricesWithTax
	 * @return boolean
	 */
	protected function getShowPricesWithoutTax($shop)
	{
		// TODO: add block preferences.
		return $shop->getDisplayPriceWithoutTax();
	}
	
	/**
	 * @return boolean
	 */
	protected function getShowShareBlock()
	{
		return ModuleService::getInstance()->isInstalled('sharethis') && $this->getConfigurationValue('showshareblock', false);
	}
	
	/**
	 * @return boolean
	 */
	protected function getShowAddToCart()
	{
		return catalog_ModuleService::getInstance()->isCartEnabled() && $this->getConfigurationValue('displayaddtocart', false);
	}
	
	/**
	 * @return boolean
	 */
	protected function getShowIfNoProduct()
	{
		return true;
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
		return LocaleService::getInstance()->trans('m.' . $this->getModuleName() . '.bo.blocks.' . $this->getName() . '.title', array('ucf', 'html'));
	}
	
	/**
	 * @return string[]|null the array should contain the following keys: 'url', 'label'
	 */
	protected function getFullListLink()
	{
		return null;
	}
	
	/**
	 * @return boolean
	 */
	protected function getSortRandom()
	{
		return $this->getConfigurationValue('sortRandom', false);
	}
			
	/**
	 * @param f_mvc_Response $response
	 * @return integer[] or null
	 */
	protected function getProductIdArray($request)
	{
		// For compatibility...
		$products = $this->getProductArray($request);
		if (is_array($products))
		{
			return DocumentHelper::getIdArrayFromDocumentArray($products);
		}
		return null;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackofficeEdition())
		{
			return website_BlockView::NONE;
		}
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		if ($shop === null)
		{
			return website_BlockView::NONE;
		}
		$request->setAttribute('shop', $shop);
		if (!$shop->getIsDefault())
		{
			$request->setAttribute('contextShopId', $shop->getId());
		}
		
		$displayConfig = $this->getDisplayConfig($shop);
		$displayConfig['itemconfig'] = $this->generateItemDisplayConfig($displayConfig, $shop);
		$request->setAttribute('displayConfig', $displayConfig);
		
		// Handle quanities field.
		$quantities = array();
		if ($request->hasParameter('quantities'))
		{
			foreach ($request->getParameter('quantities') as $id => $quantity)
			{
				$quantities[$id] = intval($quantity);
			}
		}
		$request->setAttribute('quantities', $quantities);
		
		// If the form is submitted, redirect to appropriate action.
		if (!$request->hasParameter('formBlockId') || $request->getParameter('formBlockId') == $this->getBlockId())
		{
			$this->handleActions($request, $displayConfig, $shop, $quantities);
		}
		
		$customer = null;
		if ($this->displayCustomerPrice())
		{
			$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
		}
		$request->setAttribute('customer', $customer);
		
		$productIds = $this->getProductIdArray($request);
		if ($productIds instanceof paginator_Paginator)
		{
			$request->setAttribute('products', $productIds);
		}
		elseif (is_array($productIds) && count($productIds) > 0)
		{
			// Handle randomization.
			if ($this->getSortRandom())
			{
				shuffle($productIds);
			}
			
			$maxresults = $this->getMaxresults($request);
			$page = $request->getParameter(paginator_Paginator::PAGEINDEX_PARAMETER_NAME, 1);
			if (!is_numeric($page) || $page < 1 || $page > ceil(count($productIds) / $maxresults))
			{
				$page = 1;
			}
			$this->getContext()->addCanonicalParam('page', $page > 1 ? $page : null, 'catalog');
			$paginator = new paginator_Paginator('catalog', $page, $productIds, $maxresults);
			$request->setAttribute('products', $paginator);
			
			// Handle full list link.
			if (count($productIds) > $maxresults && $this->getConfigurationValue('addlinktoall', false))
			{
				$request->setAttribute('fullListLink', $this->getFullListLink());
			}
		}
		elseif ($this->getShowIfNoProduct())
		{
			$request->setAttribute('products', null);
		}
		else
		{
			return website_BlockView::NONE;
		}
		
		// Handle block title.
		$blockTitle = $this->getConfigurationValue('blockTitle');
		if (!$blockTitle)
		{
			$blockTitle = $this->getBlockTitle();
		}
		$request->setAttribute('blockTitle', $blockTitle);
		
		return $this->getTemplateByFullName('modules_catalog', 'Catalog-Block-Productlist-' . ucfirst($this->getDisplayMode($request)));
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param array $displayConfig
	 * @param catalog_persistentdocument_shop $shop
	 * @param array $quantities
	 */
	protected function handleActions($request, $displayConfig, $shop, $quantities)
	{
		// Handle "Add to favorite".
		if ($displayConfig['showAddToFavorite'] && $request->hasParameter('addToFavorite'))
		{
			$params = array(
				'mode' => 'add',
				'refererPageId' => $this->getContext()->getId(),
				'listName' => catalog_ProductList::FAVORITE,
				'backurl' => LinkHelper::getCurrentUrl(),
				'productIds' => array_values($request->getParameter('selected_product'))
			);
			$url = LinkHelper::getActionUrl('catalog', 'UpdateList', $params);
			$this->redirectToUrl($url);
		}
			
		// Handle "Add to comparisons".
		if ($displayConfig['showAddToComparison'] && $request->hasParameter('addToComparison'))
		{
			$params = array(
				'mode' => 'add',
				'refererPageId' => $this->getContext()->getId(),
				'listName' => catalog_ProductList::COMPARISON,
				'backurl' => LinkHelper::getCurrentUrl(),
				'productIds' => array_values($request->getParameter('selected_product'))
			);
			$url = LinkHelper::getActionUrl('catalog', 'UpdateList', $params);
			$this->redirectToUrl($url);
		}
			
		// Handle "Add to cart".
		if ($displayConfig['showAddToCart'] && $request->hasParameter('addToCart'))
		{
			$productIdsToAdd = array();
			$addToCart = $request->getParameter('addToCart');
			if (is_array($addToCart))
			{
				$productIdsToAdd = $addToCart;
			}
			else if ($request->hasParameter('selected_product'))
			{
				$addToCart = $request->getParameter('selected_product');
				if (is_array($addToCart))
				{
					$productIdsToAdd = array_flip($addToCart);
				}
			}
		
			$params = array(
				'backurl' => LinkHelper::getCurrentUrl(),
				'shopId' => $shop->getId(),
				'quantities' => array_intersect_key($quantities, $productIdsToAdd),
				'productIds' => array_keys($productIdsToAdd)
			);
			$url = LinkHelper::getActionUrl('order', 'AddToCartMultiple', $params);
			$this->redirectToUrl($url);
		}
	}
	
	/**
	 * @return array
	 */
	public function getRequestModuleNames()
	{
		$names = parent::getRequestModuleNames();
		if (!in_array('catalog', $names))
		{
			$names[] = 'catalog';
		}
		return $names;
	}
	
	// Deprecated.
		
	/**
	 * @deprecated (will be removed in 4.0) use getProductIdArray
	 */
	protected function getProductArray($request)
	{
		return null;
	}
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	const DISPLAY_MODE_TABLE = 'table';
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	const DISPLAY_MODE_LIST = 'List';
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	const DEFAULT_PRODUCTS_PER_PAGE = 12;	
		
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	private function addToFavorite($product)
	{
		try
		{
			if (catalog_ModuleService::getInstance()->addFavoriteProduct($product))
			{
				$this->addedProductLabels[] = $product->getLabelAsHtml();
			}
		}
		catch (Exception $e)
		{
			$this->notAddedProductLabels[] = $product->getLabelAsHtml();
			if (Framework::isDebugEnabled())
			{
				Framework::debug($e->getMessage());
			}
		}
	}
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	protected $addedProductLabels = array();
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	protected $notAddedProductLabels = array();
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	protected function addMessagesToBlock($rootKey)
	{
		$message = $this->getMessage($this->addedProductLabels, $rootKey . '-success');
		if ($message !== null)
		{
			$this->addMessage($message);
		}
		$this->addedProductLabels = null;
		$message = $this->getMessage($this->notAddedProductLabels, $rootKey . '-error');
		if ($message !== null)
		{
			$this->addError($message);
		}
		$this->notAddedProductLabels = null;
	}
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	private function getMessage($labels, $mode)
	{
		$ls = LocaleService::getInstance();
		switch (count($labels))
		{
			case 0 :
				$message = null;
				break;
			case 1 :
				$message = $ls->trans('m.catalog.frontoffice.' . $mode . '-one', array('ucf', 'html'), array('label' => f_util_ArrayUtils::firstElement($labels)));
				break;
			default :
				$lastLabel = array_pop($labels);
				$firstLabels = implode(', ', $labels);
				$message = $ls->trans('m.catalog.frontoffice.' . $mode . '-several', array('ucf', 'html'), array('firstLabels' => $firstLabels, 'lastLabel' => $lastLabel));
				break;
		}
		return $message;
	}
}