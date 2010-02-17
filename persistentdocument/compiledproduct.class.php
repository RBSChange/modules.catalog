<?php
/**
 * catalog_persistentdocument_compiledproduct
 * @package modules.catalog
 */
class catalog_persistentdocument_compiledproduct extends catalog_persistentdocument_compiledproductbase implements indexer_IndexableDocument
{
	/**
	 * Get the indexable document
	 * @return indexer_IndexedDocument
	 */
	public function getIndexedDocument()
	{
		$product = $this->getProduct();
		$primaryShelf = $product->getPrimaryShelf($this->getWebsite());
		if ($primaryShelf !== null && $primaryShelf->getId() == $this->getShelfId())
		{
			$indexDocument = $product->getIndexedDocumentForShop($this->getShop());
			if ($indexDocument !== null)
			{
				$indexDocument->setId($this->getId());
				return $indexDocument;
			}
		}
		return null;
	}
	
	/**
	 * @return catalog_persistentdocument_shop
	 */
	public function getShop()
	{
		return DocumentHelper::getDocumentInstance($this->getShopId());
	}
	
	/**
	 * @return catalog_persistentdocument_shelf
	 */
	public function getShelf()
	{
		return DocumentHelper::getDocumentInstance($this->getShelfId());
	}
	
	/**
	 * @return website_persistentdocument_website
	 */
	public function getWebsite()
	{
		return DocumentHelper::getDocumentInstance($this->getWebsiteId());
	}
	
	/**
	 * @return website_persistentdocument_topic
	 */
	public function getTopic()
	{
		return DocumentHelper::getDocumentInstance($this->getTopicId());
	}
	
	/**
	 * @return brand_persistentdocument_brand
	 */
	public function getBrand()
	{
		$brandId = $this->getBrandId();
		if ($brandId !== null)
		{
			try
			{
				return DocumentHelper::getDocumentInstance($brandId);
			}
			catch (Exception $e)
			{
				Framework::exception($e);
			}
		}
		return null;
	}
}