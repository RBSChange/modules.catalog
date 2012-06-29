<?php
/**
 * This service groups all referencing methods for the catalog module.
 * @package modules.catalog
 * @method catalog_ReferencingService getInstance()
 */
class catalog_ReferencingService extends change_BaseService
{
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return string
	 */
	public function getPageTitleByShop($shop)
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
	 * @return string
	 */
	public function getPageDescriptionByShop($shop)
	{
		// If the shop has a page description defined, return it.
		$pageDescription = $shop->getPageDescription();
		if (!f_util_StringUtils::isEmpty($pageDescription))
		{
			return $pageDescription;
		}
		
		// Else if the shop has a description defined manually (as text, not as HTML), return it.
		$description = f_util_HtmlUtils::htmlToText($shop->getDescription(), true, true);
		if (!f_util_StringUtils::isEmpty($description))
		{
			return $description;
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return string
	 */
	public function getPageKeywordsByShop($shop)
	{
		return $shop->getPageKeywords();
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return string
	 */
	public function getPageTitleByShelf($shelf)
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
	 * @return string
	 */
	public function getPageDescriptionByShelf($shelf)
	{
		// If the shelf has a page description defined, return it.
		$pageDescription = $shelf->getPageDescription();
		if (!f_util_StringUtils::isEmpty($pageDescription))
		{
			return $pageDescription;
		}
		
		// Else if the shelf has a description defined manually (as text, not as HTML), return it.
		$description = f_util_HtmlUtils::htmlToText($shelf->getDescription(), true, true);
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
	 * @return string
	 */
	public function getPageKeywordsByShelf($shelf)
	{
		return $shelf->getPageKeywords();
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return string
	 */
	public function getPathForUrlByShelf($shelf)
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
	 * @return string
	 */
	public function getPageTitleByProduct($product)
	{
		// If the product has a page title defined manually, return it.
		if (f_util_ClassUtils::methodExists($product, 'getPageTitle'))
		{
			$pageTitle = $product->getPageTitle();
			if (!f_util_StringUtils::isEmpty($pageTitle))
			{
				return $pageTitle;
			}
		}
		
		// Else generate it.
		$label = $product->getShopPrimaryShelf($this->getCurrentShop())->getLabel();
		return sprintf(self::TITLE_FORMAT_2, $product->getFullName(), $label, $this->getCurrentWebsiteLabel());
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return string
	 */
	public function getPageDescriptionByProduct($product)
	{
		// If the product has a page description defined manually, return it.
		if (f_util_ClassUtils::methodExists($product, 'getPageDescription'))
		{
			$pageDescription = $product->getPageDescription();
			if (!f_util_StringUtils::isEmpty($pageDescription))
			{
				return $pageDescription;
			}
		}
		
		// Else if the product has a description defined (as text, not as HTML), return it.
		$description = f_util_HtmlUtils::htmlToText($product->getDescription(), true, true);
		if (!f_util_StringUtils::isEmpty($description))
		{
			return $description;
		}
		
		// Else return the parent shelf description.
		$shelf = $product->getShopPrimaryShelf($this->getCurrentShop());
		return $this->getPageDescriptionByShelf($shelf);
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return string
	 */
	public function getPageKeywordsByProduct($product)
	{
		if (f_util_ClassUtils::methodExists($product, 'getPageKeywords'))
		{
			return $product->getPageKeywords();
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return string
	 */
	public function getPathForUrlByProduct($product)
	{
		return $product->getShopPrimaryTopShelf($this->getCurrentShop())->getLabel();
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 */
	public function getMetaSubstitutionsForProduct($product)
	{
		$substitutions = array();
		$substitutions['title'] = $this->getPageTitleByProduct($product);
		$substitutions['description'] = $this->getPageDescriptionByProduct($product);
		$substitutions['keywords'] = $this->getPageKeywordsByProduct($product);
		$substitutions['label'] = $product->getLabel();
		$shop = $this->getCurrentShop();
		$primaryShelf = $product->getShopPrimaryShelf($shop);
		$primaryTopShelf = $product->getShopPrimaryTopShelf($shop);
		$substitutions['topshelfabel'] = $primaryTopShelf ? $primaryTopShelf->getLabel() : '';
		if (!DocumentHelper::equals($primaryShelf, $primaryTopShelf))
		{
			$substitutions['shelflabel'] = $primaryShelf ? $primaryShelf->getLabel() : '';
		}
		else 
		{
			$substitutions['shelflabel'] = "";
		}
		$substitutions['price'] = $product->getFormattedCurrentShopPrice();
		$substitutions['brandlabel'] = $product->getBrandLabel();
		$substitutions['coderef'] = $product->getCodeReference();
		return $substitutions;
	}

	
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
	 * @return website_persistentdocument_website
	 */
	private function getCurrentWebsite()
	{
		return website_WebsiteService::getInstance()->getCurrentWebsite();
	}
	
	/**
	 * @return catalog_persistentdocument_shop
	 */
	private function getCurrentShop()
	{
		return catalog_ShopService::getInstance()->getCurrentShop();
	}
	
	/**
	 * @return string
	 */
	private function getCurrentWebsiteLabel()
	{
		return $this->getCurrentWebsite()->getLabel();
	}
	
	/**
	 * @return string
	 */
	private function getCurrentWebsiteDescription()
	{
		return $this->getCurrentWebsite()->getDescription();
	}
	
	// Deprecated Default implementation.
		
	/**
	 * @deprecated (will be removed in 4.0) with no replacement
	 */
	public function getComparePageTitle($products)
	{
		$website = $this->getCurrentWebsite();
		$shelfLabel = f_util_ArrayUtils::firstElement($products)->getShopPrimaryShelf($this->getCurrentShop())->getLabel();
		$parameters = array('shelfLabel' => $shelfLabel, 'websiteLabel' => $website->getLabel());
		return LocaleService::getInstance()->trans('m.catalog.frontoffice.compare-page-title', array('ucf'), $parameters);
		
	}
	
	/**
	 * @deprecated (will be removed in 4.0) with no replacement
	 */
	public function getComparePageDescription($products)
	{
		$shelfLabel = f_util_ArrayUtils::firstElement($products)->getShopPrimaryShelf($this->getCurrentShop())->getLabel();
		$productLabels = array();
		foreach ($products as $product)
		{
			$productLabels[] = $product->getFullName();
		}
		$parameters = array('shelfLabel' => $shelfLabel, 'productsLabels' => implode(self::DESCRIPTION_SUBDOCUMENTS_SEPARATOR, $productLabels));
		return LocaleService::getInstance()->trans('m.catalog.frontoffice.compare-page-description', array('ucf'), $parameters);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) with no replacement
	 */
	public function setStrategy($strategyClass = '')
	{
		throw new Exception("Deprected $strategyClass strategy use service injection");
	}
		
	/**
	 * @deprecated (will be removed in 4.0) use getPageKeywordsByShop instead
	 */
	public function getDefaultPageKeywordsByShop($shop)
	{
		return $this->getPageKeywordsByShop($shop);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use getPageKeywordsByShelf instead
	 */
	public function getDefaultPageKeywordsByShelf($shelf)
	{
		return $this->getPageKeywordsByShelf($shelf);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use getPathForUrlByShelf instead
	 */
	public function getDefaultPathForUrlByShelf($shelf)
	{
		return $this->getPathForUrlByShelf($shelf);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use getPageTitleByProduct instead
	 */
	public function getDefaultPageTitleByProduct($product)
	{
		return $this->getPageTitleByProduct($product);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use getPageDescriptionByProduct instead
	 */
	public function getDefaultPageDescriptionByProduct($product)
	{
		return $this->getPageDescriptionByProduct($product);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use getPageKeywordsByProduct instead
	 */
	public function getDefaultPageKeywordsByProduct($product)
	{
		return $this->getPageKeywordsByProduct($product);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use getPathForUrlByProduct instead
	 */
	public function getDefaultPathForUrlByProduct($product)
	{
		return $this->getPathForUrlByProduct($product);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) with no replacement
	 */
	public function getDefaultComparePageTitle($products)
	{
		return $this->getComparePageTitle($products);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) with no replacement
	 */
	public function getDefaultComparePageDescription($products)
	{
		return $this->getComparePageDescription($products);
	}
}