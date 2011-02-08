<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_kititem
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_kititem extends catalog_persistentdocument_kititembase 
{	
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
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return media_persistentdocument_media
	 */
	public function getListVisual($shop)
	{
		$product = $this->getDefaultProduct();
		return $product->getDocumentService()->getListVisual($product, $shop);
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

	/**
	 * 
	 * @param catalog_persistentdocument_product $product
	 */
	public function setCurrentProduct($product)
	{
		$this->currentProduct = $product;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 */
	public function setDefaultProductForShop($shop)
	{
		if ($this->getDeclinable())
		{
			$declinedproduct = $this->getProduct()->getDeclinedproduct();
			$this->currentProduct = catalog_ProductdeclinationService::getInstance()->getPublishedDefaultDeclinationInShop($declinedproduct ,$shop);
		}
	}
	
	/**
	 * @return catalog_persistentdocument_product
	 */
	public function getDefaultProduct()
	{
		return ($this->currentProduct === null) ? $this->getProduct() : $this->currentProduct;
	}
}