<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_bundleditem
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_bundleditem extends catalog_persistentdocument_bundleditembase 
{
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return media_persistentdocument_media
	 */
	public function getDefaultVisual($shop)
	{
		$product = $this->getProduct();
		return $product->getDocumentService()->getDefaultVisual($product, $shop);
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return media_persistentdocument_media
	 */
	public function getListVisual($shop)
	{
		$product = $this->getProduct();
		return $product->getDocumentService()->getListVisual($product, $shop);
	}
	
	/**
	 * @return String
	 */
	public function getLinkTitle()
	{
		return f_util_HtmlUtils::textToHtml(f_Locale::translate('&modules.catalog.frontoffice.Titlelinkdetailproduct;', array('name' => $this->getProduct()->getLabel())));
	}
	
	public function getTitleAsHtml()
	{
		return $this->getQuantity() . ' x ' . $this->getProduct()->getLabelAsHtml();
	}	
}