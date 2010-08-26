<?php
/**
 * catalog_BlockProductBaseAction
 * @package modules.catalog
 */
abstract class catalog_BlockProductlistBaseAction extends website_BlockAction
{
	const DISPLAY_MODE_LIST = 'list';
	const DISPLAY_MODE_TABLE = 'table';
	const DEFAULT_PRODUCTS_PER_PAGE = 10;
	
	/**
	 * @return boolean
	 */
	private function useCache()
	{
		return $this->getConfigurationValue('useCache', false);
	}

	/**
	 * @param f_mvc_Request $request
	 * @return String
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
		
		// If there is a bad value, use the list mode.
		if ($displayMode != self::DISPLAY_MODE_LIST && $displayMode != self::DISPLAY_MODE_TABLE)
		{
			$displayMode = self::DISPLAY_MODE_LIST;
		}
		return $displayMode;
	}
	
	/**
	 * @return boolean
	 */
	private function displayCustomerPrice()
	{
		return catalog_ModuleService::areCustomersEnabled() && $this->getConfigurationParameter('displayCustomerPrice', 'true') === 'true';
	}	
			
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return Array
	 */
	protected function getDisplayConfig($shop)
	{
		$displayConfig = array();
		
		$displayConfig['showPricesWithTax'] = $this->getShowPricesWithTax($shop);
		$displayConfig['showPricesWithoutTax'] = $this->getShowPricesWithoutTax($shop);
		$displayConfig['showPricesWithAndWithoutTax'] = $displayConfig['showPricesWithTax'] && $displayConfig['showPricesWithoutTax'];
		$displayConfig['showPrices'] = $displayConfig['showPricesWithTax'] || $displayConfig['showPricesWithoutTax'];
		$displayConfig['showShareBlock'] = $this->getShowShareBlock();
		$displayConfig['showRatingAverage'] = $this->getConfigurationValue('displayratingaverage', false);
		$displayConfig['showProductDescription'] = $this->getConfigurationValue('displayproductdescription', false);
		$displayConfig['showProductPictograms'] = $this->getConfigurationValue('displayproductpicto', false);
		$displayConfig['showAvailability'] = $this->getConfigurationValue('displayavailability', true);
		$displayConfig['showQuantitySelector'] = $this->getConfigurationValue('activatequantityselection', true);
		$displayConfig['showSortMenu'] = $this->getConfigurationValue('displaysortmenu', false);
		$displayConfig['showDisplayModeSelector'] = $this->getConfigurationValue('displaydisplaymode', false);
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
		
		if ($this->useCache())
		{
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
		}
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
	 * @param String $name
	 * @param String $rootKey
	 * @return Array
	 */
	protected function getButtonInfo($name, $rootKey)
	{
		return array(
			'name' => $name,
			'rootKey' => $rootKey, 
			'label' => f_Locale::translate('&modules.catalog.frontoffice.' . ucfirst($rootKey) . '-button;'),
			'help' => f_Locale::translate('&modules.catalog.frontoffice.' . ucfirst($rootKey) . '-help;')
		);
	}
	
	/**
	 * @param block_BlockRequest $request
	 * @return String the display mode
	 */
	protected function getMaxresults($request)
	{
		if ($request->hasParameter('nbresultsperpage') && $request->getParameter('nbresultsperpage') > 0)
		{
			return $request->getParameter('nbresultsperpage');
		}
		$defaultProductsPerPage = ModuleService::getInstance()->getPreferenceValue('catalog', 'defaultProductsPerPage');
		return ($defaultProductsPerPage !== null) ? $defaultProductsPerPage : self::DEFAULT_PRODUCTS_PER_PAGE;
	}
	
	/**
	 * @param catalog_persistentdocument_shop getShowPricesWithTax
	 * @return Boolean
	 */
	protected function getShowPricesWithTax($shop)
	{
		// TODO: add block preferences.
		return $shop->getDisplayPriceWithTax();
	}
	
	/**
	 * @param catalog_persistentdocument_shop getShowPricesWithTax
	 * @return Boolean
	 */
	protected function getShowPricesWithoutTax($shop)
	{
		// TODO: add block preferences.
		return $shop->getDisplayPriceWithoutTax();
	}
	
	/**
	 * @return Boolean
	 */
	protected function getShowShareBlock()
	{
		return ModuleService::getInstance()->isInstalled('sharethis') && $this->getConfigurationValue('showshareblock', false);
	}
	
	/**
	 * @return Boolean
	 */
	protected function getShowAddToCart()
	{
		return catalog_ModuleService::getInstance()->isCartEnabled() && $this->getConfigurationValue('displayaddtocart', false);
	}
	
	/**
	 * @var Array<Array<String => Mixed>>
	 */
	protected $addedProductLabels = array();
	
	/**
	 * @var Array<Array<String => Mixed>>
	 */
	protected $notAddedProductLabels = array();
	
	/**
	 * @param String $successRootKey
	 * @param String $errorRootKey
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
	 * @param Array<String> $labels
	 * @param String $mode
	 * @return String
	 */
	private function getMessage($labels, $mode)
	{
		switch (count($labels))
		{
			case 0 :
				$message = null;
				break;
			case 1 :
				$message = f_Locale::translate('&modules.catalog.frontoffice.' . ucfirst($mode) . '-one;', array('label' => f_util_ArrayUtils::firstElement($labels)));
				break;
			default :
				$lastLabel = array_pop($labels);
				$firstLabels = implode(', ', $labels);
				$message = f_Locale::translate('&modules.catalog.frontoffice.' . ucfirst($mode) . '-several;', array('firstLabels' => $firstLabels, 'lastLabel' => $lastLabel));
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
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();	
		$request->setAttribute('shop', $shop);
		
		$displayConfig = $this->getDisplayConfig($shop);
		$request->setAttribute('displayConfig', $displayConfig);
		
		if ($displayConfig['showQuantitySelector'])
		{
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
		}
		
		if ($displayConfig['showAddToFavorite'])
		{
			// Handle "Add to favorite".
			if ($request->hasParameter('addToFavorite'))
			{
				foreach ($request->getParameter('selected_product') as $id)
				{
					$this->addToFavorite(DocumentHelper::getDocumentInstance($id));
				}
				$this->addMessagesToBlock('add-to-favorite');
			}
		}
		
		if ($displayConfig['showAddToCart'])
		{
			// Handle "Add to cart".
			if ($request->hasParameter('addToCart'))
			{
				$productsToAdd = array();
				
				// List all products to add.		
				$addToCart = $request->getParameter('addToCart');
				if (is_array($addToCart))
				{
					foreach (array_keys($addToCart) as $id)
					{
						$quantity = max(intval($quantities[$id]), 1);
						$productsToAdd[$id] = array('product' => DocumentHelper::getDocumentInstance($id), 'quantity' => $quantity);
					}
				}
				else if ($request->hasParameter('selected_product'))
				{	
					$addToCart = $request->getParameter('selected_product');
					if (is_array($addToCart))
					{			
						// Add each product with no quantity but the checkbox checked (with quantity of 1).
						foreach ($addToCart as $id)
						{
							if (!array_key_exists($id, $productsToAdd))
							{
								$productsToAdd[$id] = array('product' => DocumentHelper::getDocumentInstance($id), 'quantity' => max(intval($quantities[$id]), 1));
							}
						}
					}
				}
				
				// Really add the products to the cart.
				$cart = null;
				$cs = order_CartService::getInstance();
				foreach ($productsToAdd as $item)
				{
					try
					{
						$cart = $cs->addProduct($item['product'], $item['quantity']);
						$this->addedProductLabels[] = $item['product']->getLabel();
					}
					catch(order_Exception $e)
					{
						$this->notAddedProductLabels[] = $item['product']->getLabel();
						if (Framework::isDebugEnabled())
						{
							Framework::debug($e->getMessage());
						}
					}
				}
				
				if ($cart)
				{
					$cart->refresh();										
					// Set the messages.
					$this->addMessagesToBlock('add-to-cart');
				}
			}
		}
		
		$customer = null;
		if ($this->displayCustomerPrice())
		{
			$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
		}
		$request->setAttribute('customer', $customer);
		
		
		$templateName = 'Catalog-Block-Productlist-' . ucfirst($this->getDisplayMode($request));
		if ($this->useCache())
		{
			$products = $this->getProductIdArray($request);
			$templateName .= 'Cached';
		}
		else
		{
			$products = $this->getProductArray($request);
		}
		
		if ($products === null)
		{
			return website_BlockView::NONE;
		}
		if ($products instanceof paginator_Paginator)
		{
			$request->setAttribute('products', $products);
		}
		else if (is_array($products) && count($products) > 0)
		{
			$maxresults = $this->getMaxresults($request);
			$paginator = new paginator_Paginator('catalog', $request->getParameter(
					paginator_Paginator::REQUEST_PARAMETER_NAME, 1), $products, $maxresults);
			$request->setAttribute('products', $paginator);
		}
		else
		{
			$request->setAttribute('products', null);
		}
		
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
				$this->addedProductLabels[] = $product->getLabel();
			}
		}
		catch (Exception $e)
		{
			$this->notAddedProductLabels[] = $product->getLabel();
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
}