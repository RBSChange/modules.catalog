<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_kititem
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_kititem extends catalog_persistentdocument_kititembase 
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

	
	//TEMPLATING FUNCTION
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return media_persistentdocument_media
	 */
	public function getDefaultVisual($shop)
	{
		$product = $this->getDefaultProduct();
		return $product->getDocumentService()->getDefaultVisual($product, $shop);
	}
	
	public function getTitleAsHtml()
	{
		return $this->getQuantity() . ' x ' . $this->getDefaultProduct()->getLabelAsHtml();
	}

	public function getCurrentKey()
	{
		return 'k'. $this->getId();
	}
	
	/**
	 * @param catalog_persistentdocument_kit $kit
	 */
	public function setCurrentKit($kit)
	{
		$this->currentKit = $kit;
	}
	
	/**
	 * @var catalog_persistentdocument_kit
	 */
	private $currentKit;
	
	/**
	 * @return catalog_persistentdocument_kit
	 */
	public function getCurrentKit()
	{
		return $this->currentKit;
	}

	/**
	 * @var catalog_persistentdocument_product
	 */
	private $currentProduct;
	
	/**
	 * @return catalog_persistentdocument_product
	 */
	public function getCurrentProduct()
	{
		return $this->currentProduct;
	}

	public function setCurrentProduct($product)
	{
		$this->currentProduct = $product;
	}
	
	/**
	 * @return catalog_persistentdocument_product
	 */
	public function getDefaultProduct()
	{
		return $this->currentProduct === null ? $this->getProduct() : $this->currentProduct;
	}	
}