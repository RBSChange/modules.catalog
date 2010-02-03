<?php
/**
 * catalog_ReferencingService
 * @package modules.catalog
 * 
 * This service groups all referencing methods for the catalog module.
 */
class catalog_ReferencingService extends BaseService
{
	/**
	 * Singleton
	 * @var catalog_ProductService
	 */
	private static $instance = null;
	
	/**
	 * @return catalog_ProductService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	private $strategy = null;
	
	/**
	 * Specific constructor, instanciating the strategy.
	 */
	protected function __construct()
	{
		parent::__construct();
		
		// Instanciate the strategy.
		try
		{
			$strategyClass = Framework::getConfiguration('modules/catalog/referencingStrategyClass');
			$this->setStrategy($strategyClass);
		}
		catch (ConfigurationException $e)
		{
			// Nothing to do here.
		}
	}
	
	/**
	 * @param String $strategyClass
	 */
	public function setStrategy($strategyClass = '')
	{
		if (strlen($strategyClass) > 0)
		{
			$this->strategy = new $strategyClass();
		}
		else
		{
			$this->strategy = null;
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return String
	 */
	public function getPageTitleByShop($shop)
	{
		if (!is_null($this->strategy) && is_callable(array($this->strategy, 'getPageTitleByShop')))
		{
			return $this->strategy->getPageTitleByShop($shop);
		}
		else
		{
			return $this->getDefaultPageTitleByShop($shop);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return String
	 */
	public function getPageDescriptionByShop($shop)
	{
		if (!is_null($this->strategy) && is_callable(array($this->strategy, 'getPageDescriptionByShop')))
		{
			return $this->strategy->getPageDescriptionByShop($shop);
		}
		else
		{
			return $this->getDefaultPageDescriptionByShop($shop);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return String
	 */
	public function getPageKeywordsByShop($shop)
	{
		if (!is_null($this->strategy) && is_callable(array($this->strategy, 'getPageKeywordsByShop')))
		{
			return $this->strategy->getPageKeywordsByShop($shop);
		}
		else
		{
			return $this->getDefaultPageKeywordsByShop($shop);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return String
	 */
	public function getPageTitleByShelf($shelf)
	{
		if (!is_null($this->strategy) && is_callable(array($this->strategy, 'getPageTitleByShelf')))
		{
			return $this->strategy->getPageTitleByShelf($shelf);
		}
		else
		{
			return $this->getDefaultPageTitleByShelf($shelf);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return String
	 */
	public function getPageDescriptionByShelf($shelf)
	{
		if (!is_null($this->strategy) && is_callable(array($this->strategy, 'getPageDescriptionByShelf')))
		{
			return $this->strategy->getPageDescriptionByShelf($shelf);
		}
		else
		{
			return $this->getDefaultPageDescriptionByShelf($shelf);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return String
	 */
	public function getPageKeywordsByShelf($shelf)
	{
		if (!is_null($this->strategy) && is_callable(array($this->strategy, 'getPageKeywordsByShelf')))
		{
			return $this->strategy->getPageKeywordsByShelf($shelf);
		}
		else
		{
			return $this->getDefaultPageKeywordsByShelf($shelf);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return String
	 */
	public function getPathForUrlByShelf($shelf)
	{
		if (!is_null($this->strategy) && is_callable(array($this->strategy, 'getPathForUrlByShelf')))
		{
			return $this->strategy->getPathForUrlByShelf($shelf);
		}
		else
		{
			return $this->getDefaultPathForUrlByShelf($shelf);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return String
	 */
	public function getPageTitleByProduct($product)
	{
		if (!is_null($this->strategy) && is_callable(array($this->strategy, 'getPageTitleByProduct')))
		{
			return $this->strategy->getPageTitleByProduct($product);
		}
		else
		{
			return $this->getDefaultPageTitleByProduct($product);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return String
	 */
	public function getPageDescriptionByProduct($product)
	{
		if (!is_null($this->strategy) && is_callable(array($this->strategy, 'getPageDescriptionByProduct')))
		{
			return $this->strategy->getPageDescriptionByProduct($product);
		}
		else
		{
			return $this->getDefaultPageDescriptionByProduct($product);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return String
	 */
	public function getPageKeywordsByProduct($product)
	{
		if (!is_null($this->strategy) && is_callable(array($this->strategy, 'getPageKeywordsByProduct')))
		{
			return $this->strategy->getPageKeywordsByProduct($product);
		}
		else
		{
			return $this->getDefaultPageKeywordsByProduct($product);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return String
	 */
	public function getPathForUrlByProduct($product)
	{
		if (!is_null($this->strategy) && is_callable(array($this->strategy, 'getPathForUrlByProduct')))
		{
			return $this->strategy->getPathForUrlByProduct($product);
		}
		else
		{
			return $this->getDefaultPathForUrlByProduct($product);
		}
	}
	
	/**
	 * @param Array<catalog_persistentdocument_product> $produts
	 * @return String
	 */
	public function getComparePageTitle($products)
	{
		if (!is_null($this->strategy) && is_callable(array($this->strategy, 'getComparePageTitle')))
		{
			return $this->strategy->getComparePageTitle($products);
		}
		else
		{
			return $this->getDefaultComparePageTitle($products);
		}
	}
	
	/**
	 * @param Array<catalog_persistentdocument_product> $products
	 * @return String
	 */
	public function getComparePageDescription($products)
	{
		if (!is_null($this->strategy) && is_callable(array($this->strategy, 'getComparePageDescription')))
		{
			return $this->strategy->getComparePageDescription($products);
		}
		else
		{
			return $this->getDefaultComparePageDescription($products);
		}
	}
	
	// Default implementation.
	
	/**
	 * Title in two parts.
	 */
	const TITLE_FORMAT_1 = '%s : %s';
	
	/**
	 * Title in three parts.
	 */
	const TITLE_FORMAT_2 = '%s, %s : %s';
	
	/**
	 * Description in to parts.
	 */
	const DESCRIPTION_SUBDOCUMENTS_SEPARATOR = ', ';
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return String
	 */
	private function getDefaultPageTitleByShop($shop)
	{
		// If the shop has a page title defined, return it.
		$pageTitle = $shop->getPageTitle();
		if (!f_util_StringUtils::isEmpty($pageTitle))
		{
			return $pageTitle;
		}
		
		// Else generate it.
		return sprintf(self::TITLE_FORMAT_1, $shop->getLabel(), $this->getCurrentWebsiteLabel());
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return String
	 */
	private function getDefaultPageDescriptionByShop($shop)
	{
		// If the shop has a page description defined, return it.
		$pageDescription = $shop->getPageDescription();
		if (!f_util_StringUtils::isEmpty($pageDescription))
		{
			return $pageDescription;
		}
		
		// Else if the shop has a description defined manually (as text, not as HTML), return it.
		$description = f_util_StringUtils::htmlToText($shop->getDescription(), true, true);
		if (!f_util_StringUtils::isEmpty($description))
		{
			return $description;
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return String
	 */
	public function getDefaultPageKeywordsByShop($shop)
	{
		return $shop->getPageKeywords();
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return String
	 */
	private function getDefaultPageTitleByShelf($shelf)
	{
		// If the shelf has a page title defined, return it.
		$pageTitle = $shelf->getPageTitle();
		if (!f_util_StringUtils::isEmpty($pageTitle))
		{
			return $pageTitle;
		}
		
		// Else generate it.
		$parentShelf = $shelf->getParentShelf();
		if ($parentShelf !== null)
		{
			$pageTitle = sprintf(self::TITLE_FORMAT_2, $shelf->getLabel(), $parentShelf->getLabel(), $this->getCurrentWebsiteLabel());
		}
		else
		{
			$pageTitle = sprintf(self::TITLE_FORMAT_1, $shelf->getLabel(), $this->getCurrentWebsiteLabel());
		}
		return $pageTitle;
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return String
	 */
	private function getDefaultPageDescriptionByShelf($shelf)
	{
		// If the shelf has a page description defined, return it.
		$pageDescription = $shelf->getPageDescription();
		if (!f_util_StringUtils::isEmpty($pageDescription))
		{
			return $pageDescription;
		}
		
		// Else if the shelf has a description defined manually (as text, not as HTML), return it.
		$description = f_util_StringUtils::htmlToText($shelf->getDescription(), true, true);
		if (!f_util_StringUtils::isEmpty($description))
		{
			return $description;
		}
		
		// Else return the parent shelf's description.
		$parentShelf = $shelf->getParentShelf();
		if (!is_null($parentShelf))
		{
			return $this->getPageDescriptionByShelf($shelf->getParentShelf());
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return String
	 */
	public function getDefaultPageKeywordsByShelf($shelf)
	{
		return $shelf->getPageKeywords();
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return String
	 */
	public function getDefaultPathForUrlByShelf($shelf)
	{
		$topshelf = $shelf->getDocumentService()->getTopShelfByShelf($shelf);
		
		if (!is_null($topshelf) && $shelf->getId() != $topshelf->getId())
		{
			return $topshelf->getLabel();
		}
		
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return String
	 */
	public function getDefaultPageTitleByProduct($product)
	{
		// If the product has a page title defined manually, return it.
		$pageTitle = $product->getPageTitle();
		if (!f_util_StringUtils::isEmpty($pageTitle))
		{
			return $pageTitle;
		}
		
		// Else generate it.
		$label = $product->getPrimaryShelf($this->getCurrentWebsite())->getLabel();
		return sprintf(self::TITLE_FORMAT_2, $product->getFullName(), $label, $this->getCurrentWebsiteLabel());
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return String
	 */
	public function getDefaultPageDescriptionByProduct($product)
	{
		// If the product has a page description defined manually, return it.
		$pageDescription = $product->getPageDescription();
		if (!f_util_StringUtils::isEmpty($pageDescription))
		{
			return $pageDescription;
		}
		
		// Else if the product has a description defined (as text, not as HTML), return it.
		$description = f_util_StringUtils::htmlToText($product->getDescription(), true, true);
		if (!f_util_StringUtils::isEmpty($description))
		{
			return $description;
		}
		
		// Else return the parent shelf description.
		$shelf = $product->getPrimaryShelf($this->getCurrentWebsite());
		return $this->getPageDescriptionByShelf($shelf);
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return String
	 */
	public function getDefaultPageKeywordsByProduct($product)
	{
		return $product->getPageKeywords();
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return String
	 */
	public function getDefaultPathForUrlByProduct($product)
	{
		return $product->getPrimaryTopShelf($this->getCurrentWebsite())->getLabel();
	}
	
	/**
	 * @param Array<catalog_persistentdocument_product> $produts
	 * @return String
	 */
	public function getDefaultComparePageTitle($products)
	{
		$website = $this->getCurrentWebsite();
		$shelfLabel = f_util_ArrayUtils::firstElement($products)->getPrimaryShelf($website)->getLabel();
		$parameters = array('shelfLabel' => $shelfLabel, 'websiteLabel' => $website->getLabel());
		return f_Locale::translate('&modules.catalog.frontoffice.Compare-page-title;', $parameters);
	}
	
	/**
	 * @param Array<catalog_persistentdocument_product> $products
	 * @return String
	 */
	public function getDefaultComparePageDescription($products)
	{
		$website = $this->getCurrentWebsite();
		$shelfLabel = f_util_ArrayUtils::firstElement($products)->getPrimaryShelf($website)->getLabel();
		$productLabels = array();
		foreach ($products as $product)
		{
			$productLabels[] = $product->getFullName();
		}
		$parameters = array('shelfLabel' => $shelfLabel, 'productsLabels' => implode(self::DESCRIPTION_SUBDOCUMENTS_SEPARATOR, $productLabels));
		return f_Locale::translate('&modules.catalog.frontoffice.Compare-page-description;', $parameters);
	}
	
	/**
	 * @return website_persistentdocument_website
	 */
	private function getCurrentWebsite()
	{
		return website_WebsiteModuleService::getInstance()->getCurrentWebsite();
	}
	
	/**
	 * @return String
	 */
	private function getCurrentWebsiteLabel()
	{
		return $this->getCurrentWebsite()->getLabel();
	}
	
	/**
	 * @return String
	 */
	private function getCurrentWebsiteDescription()
	{
		return $this->getCurrentWebsite()->getDescription();
	}
}