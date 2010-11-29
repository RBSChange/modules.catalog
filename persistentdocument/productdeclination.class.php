<?php
/**
 * catalog_persistentdocument_productdeclination
 * @package catalog.persistentdocument
 */
class catalog_persistentdocument_productdeclination extends catalog_persistentdocument_productdeclinationbase implements catalog_StockableDocument
{
	/**
	 * @var catalog_persistentdocument_declinedproduct
	 */
	private $relatedDeclinedProduct;
	
	/**
	 * @param boolean $refreshCache
	 * @return catalog_persistentdocument_declinedproduct
	 */
	public function getRelatedDeclinedProduct($refreshCache = false)
	{
		if ($this->relatedDeclinedProduct === null || $refreshCache)
		{
			$this->relatedDeclinedProduct = f_util_ArrayUtils::firstElement($this->getDeclinedproductArrayInverse(0, 1));
		}
		return $this->relatedDeclinedProduct;
	}
	
	/**
	 * @return catalog_persistentdocument_product
	 */
	public function getProductToCompile()
	{
		return $this->getRelatedDeclinedProduct();
	}
	
	/**
	 * @return boolean
	 */
	public function isCompilable()
	{
		return false;
	}
	
	/**
	 * Get the indexable document
	 * @param catalog_persistentdocument_shop $shop
	 * @return indexer_IndexedDocument
	 */
	public function getIndexedDocumentForShop($shop)
	{
		return null;
	}
	
	
	/**
	 * @return String
	 */
	public function getDetailBlockName()
	{
		return 'declinedproduct';
	}

	/**
	 * @return String
	 */
	public function getPageTitle()
	{
		return $this->getRelatedDeclinedProduct()->getPageTitle();
	}
	
	/**
	 * @return String
	 */
	public function getPageDescription()
	{
		return $this->getRelatedDeclinedProduct()->getPageDescription();
	}
	
	/**
	 * @return String
	 */
	public function getPageKeywords()
	{
		return $this->getRelatedDeclinedProduct()->getPageKeywords();
	}
	
	/**
	 * @return String
	 */
	public function getLabelForUrl()
	{
		return $this->getRelatedDeclinedProduct()->getLabelForUrl();
	}

	/**
	 * @return media_persistentdocument_media[]
	 */
	public function getAllVisuals($shop)
	{
		$declinedProduct = $this->getRelatedDeclinedProduct();
		$visuals = array();
		$visual1 = $this->getVisual();
		if ($visual1 !== null)
		{
			$visuals[] = $visual1;
			$visual2 = $declinedProduct->getVisual();
			if ($visual2 !== null)
			{
				$visuals[] = $visual2;
			}
		}
		else
		{
			$visuals[] = $declinedProduct->getDefaultVisual($shop);
		}
		$visuals = array_unique(array_merge($visuals, $declinedProduct->getAdditionnalVisualArray()));
		return $visuals;
	}
	
	/**
	 * @return boolean
	 */
	public function handleRelatedProducts()
	{
		return false;
	}

	/**
	 * @return boolean
	 */
	public function isPricePanelEnabled()
	{
		return !$this->getRelatedDeclinedProduct()->getSynchronizePrices();
	}
	
	/**
	 * @return string
	 */
	public function getPricePanelDisabledMessage()
	{
		if (!$this->isPricePanelEnabled())
		{
			return f_Locale::translate('&modules.catalog.bo.doceditor.panel.prices.disabled.Productdeclination-synchronized;');
		}
		return null;
	}
}