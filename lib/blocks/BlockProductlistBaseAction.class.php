<?php
/**
 * catalog_BlockProductBaseAction
 * @package modules.catalog
 */
abstract class catalog_BlockProductlistBaseAction extends website_BlockAction
{
	const DISPLAY_MODE_LIST = 'List';
	const DEFAULT_PRODUCTS_PER_PAGE = 12;

	/**
	 * @param f_mvc_Request $request
	 * @return string
	 */
	protected function getDisplayMode($request)
	{
		if ($request->hasParameter('displayMode'))
		{
			$displayMode = $request->getParameter('displayMode');
		}
		else
		{
			$displayMode = $this->getConfigurationValue('displayMode', self::DISPLAY_MODE_LIST);
		}
		return $displayMode;
	}
	
	/**
	 * @return boolean
	 */
	protected function displayCustomerPrice()
	{
		return catalog_ModuleService::areCustomersEnabled() && $this->getConfigurationParameter('displayCustomerPrice', 'true') === 'true';
	}	
			
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return array
	 */
	protected function getDisplayConfig($shop)
	{
		$displayConfig = array();
		
		$displayConfig['blockId'] = $this->getBlockId();
		$displayConfig['showPricesWithTax'] = $this->getShowPricesWithTax($shop);
		$displayConfig['showPricesWithoutTax'] = $this->getShowPricesWithoutTax($shop);
		$displayConfig['showPricesWithAndWithoutTax'] = $displayConfig['showPricesWithTax'] && $displayConfig['showPricesWithoutTax'];
		$displayConfig['showPrices'] = $displayConfig['showPricesWithTax'] || $displayConfig['showPricesWithoutTax'];
		$displayConfig['showShareBlock'] = $this->getShowShareBlock();
		$displayConfig['showRatingAverage'] = catalog_ModuleService::getInstance()->areCommentsEnabled() ? $this->getConfigurationValue('displayratingaverage', false) : false;
		$displayConfig['showProductDescription'] = $this->getConfigurationValue('displayproductdescription', false);
		$displayConfig['showProductPictograms'] = $this->getConfigurationValue('displayproductpicto', false);
		$displayConfig['showAvailability'] = $shop->getDisplayOutOfStock() && $this->getConfigurationValue('displayavailability', true);
		$displayConfig['showQuantitySelector'] = $this->getConfigurationValue('activatequantityselection', true);
		$displayConfig['showSortMenu'] = $this->getConfigurationValue('displaysortmenu', false);
		$displayConfig['showResultCountSelector'] = $this->getConfigurationValue('displaynbresultsperpage', false);
		$displayConfig['showDiscountFilter'] = $this->getConfigurationValue('displaydiscountfilter', false);
		$displayConfig['showBrandOrder'] = $this->getConfigurationValue('Displaybrandorder', false);
		
		$displayConfig['controlsmodule'] = 'catalog';
		$displayConfig['controlstemplate'] = 'Catalog-Inc-ProductListOrderOptions';
		
		$globalButtons = array();
		$displayConfig['showAddToCart'] = $this->getShowAddToCart();
		if ($displayConfig['showAddToCart'])
		{
			$globalButtons[] = $this->getButtonInfo('addToCart', 'add-to-cart');
		}
		$displayConfig['showAddToFavorite'] = $this->getConfigurationValue('showAddToFavorite', false);
		if ($displayConfig['showAddToFavorite'])
		{
			$globalButtons[] = $this->getButtonInfo('addToFavorite', 'add-to-favorite');
		}
		$displayConfig['globalButtons'] = $globalButtons;
		$displayConfig['showCheckboxes'] = count($globalButtons) > 0;
		
		$displayConfig['displayCustomerPrice'] = $this->displayCustomerPrice();
		
		$displayConfig['itemconfig'] = array(
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
		);
		return $displayConfig;
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
			'label' => $ls->transFO('m.catalog.frontoffice.' . $rootKey . '-button', array('ucf', 'html')),
			'help' => $ls->transFO('m.catalog.frontoffice.' . $rootKey . '-help', array('ucf', 'html'))
		);
	}
	
	/**
	 * @param block_BlockRequest $request
	 * @return string the display mode
	 */
	protected function getMaxresults($request)
	{
		$nbresultsperpage = $this->findParameterValue("nbresultsperpage");
		if ($nbresultsperpage > 0)
		{
			return $nbresultsperpage;
		}
		$defaultProductsPerPage = self::DEFAULT_PRODUCTS_PER_PAGE;
		$currentShop = catalog_ShopService::getInstance()->getCurrentShop();
		if ($currentShop)
		{
			$defaultProductsPerPage = $currentShop->getNbproductperpage();
		}
		return $defaultProductsPerPage;
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
		return LocaleService::getInstance()->transFO('m.' . $this->getModuleName() . '.bo.blocks.' . $this->getName() . '.title', array('ucf'));
	}
	
	/**
	 * @return string[]|null the array should contain the following keys: 'url', 'label'
	 */
	protected function getFullListLink()
	{
		return null;
	}
	
	/**
	 * @var array<Array<String => Mixed>>
	 */
	protected $addedProductLabels = array();
	
	/**
	 * @var array<Array<String => Mixed>>
	 */
	protected $notAddedProductLabels = array();
	
	/**
	 * @param string $successRootKey
	 * @param string $errorRootKey
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
	 * @param array<String> $labels
	 * @param string $mode
	 * @return string
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
				$message = $ls->transFO('m.catalog.frontoffice.' . $mode . '-one', array('ucf', 'html'), array('label' => f_util_ArrayUtils::firstElement($labels)));
				break;
			default :
				$lastLabel = array_pop($labels);
				$firstLabels = implode(', ', $labels);
				$message = $ls->transFO('m.catalog.frontoffice.' . $mode . '-several', array('ucf', 'html'), array('firstLabels' => $firstLabels, 'lastLabel' => $lastLabel));
				break;
		}
		return $message;
	}
	
	/**
	 * @param f_mvc_Response $response
	 * @return catalog_persistentdocument_product[] or null
	 */
	protected function getProductArray($request)
	{
		return null;
	}
	
	/**
	 * @param f_mvc_Response $response
	 * @return integer[] or null
	 */
	protected function getProductIdArray($request)
	{
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
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		if ($shop === null)
		{
			return website_BlockView::NONE;
		}
		$request->setAttribute('shop', $shop);
		if (!$shop->getIsDefault())
		{
			$request->setAttribute('contextShopId', $shop->getId());
			$systemTopic = $this->getContext()->getParent();
		}
		
		$displayConfig = $this->getDisplayConfig($shop);
		$request->setAttribute('displayConfig', $displayConfig);
		
		// Handle quanities field.
		$quantities = array();
		if ($request->hasParameter('quantities'))
		{
			foreach ($request->getParameter('quantities') as $id => $quantity)
			{
				$quantities[$id] =  intval($quantity);
			}
		}
		$request->setAttribute('quantities', $quantities);
		
		if (!$request->hasParameter('formBlockId') || $request->getParameter('formBlockId') == $this->getBlockId())
		{
			// Handle "Add to favorite".
			if ($displayConfig['showAddToFavorite'] && $request->hasParameter('addToFavorite'))
			{
				foreach ($request->getParameter('selected_product') as $id)
				{
					$this->addToFavorite(DocumentHelper::getDocumentInstance($id));
				}
				$this->addMessagesToBlock('add-to-favorite');
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
		
		$customer = null;
		if ($this->displayCustomerPrice())
		{
			$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
		}
		$request->setAttribute('customer', $customer);
		
		$templateName = 'Catalog-Block-Productlist-' . ucfirst($this->getDisplayMode($request));
		$productIds = $this->getProductIdArray($request);				
		if (is_array($productIds) && count($productIds) > 0)
		{
			$maxresults = $this->getMaxresults($request);
			$page = $request->getParameter(paginator_Paginator::PAGEINDEX_PARAMETER_NAME, 1);
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
		
		return $this->getTemplateByFullName('modules_catalog', $templateName);
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
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
	 * @deprecated (will be removed in 4.0)
	 */
	const DISPLAY_MODE_TABLE = 'table';
}