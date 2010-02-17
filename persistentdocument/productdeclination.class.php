<?php
/**
 * catalog_persistentdocument_productdeclination
 * @package catalog.persistentdocument
 */
class catalog_persistentdocument_productdeclination extends catalog_persistentdocument_productdeclinationbase implements catalog_StockableDocument
{
	
	/**
	 * @return catalog_persistentdocument_declinedproduct
	 */
	public function getRelatedDeclinedProduct()
	{
		return f_util_ArrayUtils::firstElement($this->getDeclinedproductArrayInverse(0, 1));
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
	 * @param website_pesistentdocument_website $website
	 * @return catalog_persistentdocument_shelf
	 */
	public function getPrimaryShelf($website = null)
	{
		return $this->getRelatedDeclinedProduct()->getPrimaryShelf($website);
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
}