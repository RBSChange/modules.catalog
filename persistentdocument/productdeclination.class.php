<?php
/**
 * catalog_persistentdocument_productdeclination
 * @package catalog.persistentdocument
 */
class catalog_persistentdocument_productdeclination extends catalog_persistentdocument_productdeclinationbase 
	implements catalog_StockableDocument, catalog_DeclinableProduct
{

	/**
	 * @return String
	 */
	public function getDetailBlockName()
	{
		return 'declinedproduct';
	}
	
	public function getLabelForUrl()
	{
		if ($this->getDeclinedproduct()->getShowAxeInList() == 0)
		{
			return $this->getDeclinedproduct()->getLabel();
		}
		return $this->getLabel();
	}

	/**
	 * @return media_persistentdocument_media[]
	 */
	public function getAllVisuals($shop)
	{
		$declinedProduct = $this->getDeclinedproduct();
		
		$visuals = array();
		
		$visual1 = $this->getVisual();	
		if ($visual1 !== null)
		{
			$visuals[] = $visual1;
		}
		$visual2 = $declinedProduct->getVisual();
		if ($visual2 !== null)
		{
			$visuals[] = $visual2;
		}
		
		$visuals = array_unique(array_merge($visuals, $declinedProduct->getAdditionnalVisualArray()));
		return $visuals;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return media_persistentdocument_media
	 */
	public function getDefaultVisual($shop = null)
	{
		$media = $this->getVisual();
		if ($media === null)
		{
			$media = $this->getDeclinedproduct()->getDefaultVisual($shop);
		}
		return $media;
	}
	
	/**
	 * @return boolean
	 */
	public function isPricePanelEnabled()
	{
		return !$this->getDeclinedproduct()->getSynchronizePrices();
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
	
	//Front office templating
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_productdeclination[]
	 */
	public function getPublishedDeclinationsInShop($shop = null)
	{
		if ($shop === null)
		{
			$shop = catalog_ShopService::getInstance()->getCurrentShop();
			if ($shop === null) {return  array();}
		}
		return $this->getDocumentService()->getPublishedDeclinationsInShop($this->getDeclinedproduct(), $shop);
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_productdeclination
	 */
	public function getPublishedDefaultDeclinationInShop($shop = null)
	{
		if ($shop === null)
		{
			$shop = catalog_ShopService::getInstance()->getCurrentShop();
			if ($shop === null) {return  array();}
		}
		return $this->getDocumentService()->getPublishedDefaultDeclinationInShop($this->getDeclinedproduct(), $shop);
	}
	
	/**
	 * @return catalog_persistentdocument_product[]
	 */
	public function getDeclinations()
	{
		return $this->getDocumentService()->getArrayByDeclinedProduct($this->getDeclinedproduct());
	}
	
	/**
	 * @return string
	 */
	public function getAxe1Label()
	{
		return $this->getDocumentService()->getAxeLabel($this, 1);
	}
	
	/**
	 * @return string
	 */
	public function getAxe1LabelAsHtml()
	{
		return f_util_HtmlUtils::textToHtml($this->getAxe1Label());
	}
	
	/**
	 * @return string
	 */
	public function getAxe1TitleAsHtml()
	{
		return $this->getDocumentService()->getAxeTitleAsHtml($this, 1);
	}
	
	/**
	 * @return boolean
	 */
	public function useAxe2()
	{
		return ($this->getDeclinedproduct()->getAxe2() !== null);
	}
	
	/**
	 * @return string
	 */
	public function getAxe2Label()
	{
		return $this->getDocumentService()->getAxeLabel($this, 2);
	}
	
	/**
	 * @return string
	 */
	public function getAxe2LabelAsHtml()
	{
		return f_util_HtmlUtils::textToHtml($this->getAxe2Label());
	}
	
	/**
	 * @return string
	 */
	public function getAxe2TitleAsHtml()
	{
		return $this->getDocumentService()->getAxeTitleAsHtml($this, 2);
	}
	
	/**
	 * @return boolean
	 */
	public function useAxe3()
	{
		return ($this->getDeclinedproduct()->getAxe3() !== null);
	}
	
	/**
	 * @return string
	 */
	public function getAxe3Label()
	{
		return $this->getDocumentService()->getAxeLabel($this, 3);
	}
	
	/**
	 * @return string
	 */
	public function getAxe3LabelAsHtml()
	{
		return f_util_HtmlUtils::textToHtml($this->getAxe3Label());
	}
	
	/**
	 * @return string
	 */
	public function getAxe3TitleAsHtml()
	{
		return $this->getDocumentService()->getAxeTitleAsHtml($this, 3);
	}
	
	/**
	 * @return string
	 */
	public function getFullLabel()
	{
		return $this->getDocumentService()->getNavigationLabel($this, 3);
	}
	
	/**
	 * @return string
	 */
	public function getFullLabelAsHtml()
	{
		return f_util_HtmlUtils::textToHtml($this->getFullLabel());
	}
	
	/**
	 * @return string
	 */
	public function getOrderLabel()
	{
		return $this->getFullLabel();
	}
	
	/**
	 * @return string
	 */
	public function getOrderLabelAsHtml()
	{
		return LinkHelper::getLink($this, null, 'link', null, array('label' => $this->getFullLabel()));
	}
}