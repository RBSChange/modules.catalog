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
}