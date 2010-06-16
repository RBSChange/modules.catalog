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
		$configuration = $this->getConfiguration();
		$displayConfig = array();
		$displayConfig['showPricesWithTax'] = $this->getShowPricesWithTax($shop);
		$displayConfig['showPricesWithoutTax'] = $this->getShowPricesWithoutTax($shop);
		$displayConfig['showPricesWithAndWithoutTax'] = $displayConfig['showPricesWithTax'] && $displayConfig['showPricesWithoutTax'];
		$displayConfig['showPrices'] = $displayConfig['showPricesWithTax'] || $displayConfig['showPricesWithoutTax'];
		$displayConfig['showShareBlock'] = $this->getShowShareBlock();
		$displayConfig['showRatingAverage'] = $configuration->getDisplayratingaverage();
		$displayConfig['showProductDescription'] = $configuration->getDisplayproductdescription();
		$displayConfig['showProductPictograms'] = $configuration->getDisplayproductpicto();
		$displayConfig['showAvailability'] = $configuration->getDisplayavailability();
		$displayConfig['showQuantitySelector'] = $configuration->getActivatequantityselection();
		$displayConfig['showSortMenu'] = $configuration->getDisplaysortmenu();
		$displayConfig['showDisplayModeSelector'] = $configuration->getDisplaydisplaymode();
		$displayConfig['showResultCountSelector'] = $configuration->getDisplaynbresultsperpage();
		$displayConfig['showDiscountFilter'] = $configuration->getDisplaydiscountfilter();
		$displayConfig['showBrandOrder'] = $configuration->getDisplaybrandorder();
		
		$globalButtons = array();
		$displayConfig['showAddToCart'] = $this->getShowAddToCart();
		if ($displayConfig['showAddToCart'])
		{
			$globalButtons[] = $this->getButtonInfo('addToCart', 'add-to-cart');
		}
		$displayConfig['showAddToFavorite'] = $configuration->getShowAddToFavorite();
		if ($displayConfig['showAddToFavorite'])
		{
			$globalButtons[] = $this->getButtonInfo('addToFavorite', 'add-to-favorite');
		}
		$displayConfig['globalButtons'] = $globalButtons;
		$displayConfig['showCheckboxes'] = count($globalButtons) > 0;		
		return $displayConfig;
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
			'help' => f_Locale::translate('&modules.catalog.frontoffice.' . ucfirst($rootKey) . '-help;'),
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
		return ModuleService::getInstance()->isInstalled('sharethis') && $this->getConfiguration()->getShowshareblock();
	}

	/**
	 * @return Boolean
	 */
	protected function getShowAddToCart()
	{
		return catalog_ModuleService::getInstance()->isCartEnabled() && $this->getConfiguration()->getDisplayaddtocart();
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
				$message = f_Locale::translate('&modules.catalog.frontoffice.'.ucfirst($mode).'-one;', array('label' => f_util_ArrayUtils::firstElement($labels)));
				break;
			default :
				$lastLabel = array_pop($labels);
				$firstLabels = implode(', ', $labels);
				$message = f_Locale::translate('&modules.catalog.frontoffice.'.ucfirst($mode).'-several;', array('firstLabels' => $firstLabels, 'lastLabel' => $lastLabel));
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
			$configuration = $this->getConfiguration();		
			$displayMode = $configuration->getDisplayMode();
		}

		// If there is a bad value, use the list mode.
		if ($displayMode != self::DISPLAY_MODE_LIST	&& $displayMode != self::DISPLAY_MODE_TABLE)
		{
			$displayMode = self::DISPLAY_MODE_LIST;
		}
		return $displayMode;
	}	
}