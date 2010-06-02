<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_bundleditem
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_bundleditem extends catalog_persistentdocument_bundleditembase 
{
	/**
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
//	protected function addTreeAttributes($moduleName, $treeType, &$nodeAttributes)
//	{
//	}
	
	/**
	 * @param string $actionType
	 * @param array $formProperties
	 */
//	public function addFormProperties($propertiesNames, &$formProperties)
//	{	
//	}

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