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
		if ($this->getShowInList())
		{
			$product = $this->getProduct();
			$indexDocument = $product->getIndexedDocumentForShop($this->getShop());
			if ($indexDocument !== null)
			{
				$indexDocument->setId($this->getId());
				$indexDocument->setDocumentModel("modules_catalog/compiledproduct");		
				$this->getDocumentService()->indexFacets($this, $indexDocument);
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
		return DocumentHelper::getDocumentInstance($this->getShopId(), 'modules_catalog/shop');
	}
	
	/**
	 * @return catalog_persistentdocument_shelf
	 */
	public function getShelf()
	{
		return DocumentHelper::getDocumentInstance($this->getShelfId(), 'modules_catalog/shelf');
	}
	
	/**
	 * @return catalog_persistentdocument_topshelf
	 */
	public function getTopShelf()
	{
		return catalog_persistentdocument_topshelf::getInstanceById($this->getTopshelfId());
	}
	
	/**
	 * @return website_persistentdocument_website
	 */
	public function getWebsite()
	{
		return DocumentHelper::getDocumentInstance($this->getWebsiteId(), 'modules_website/website');
	}
	
	/**
	 * @return website_persistentdocument_topic
	 */
	public function getTopic()
	{
		return DocumentHelper::getDocumentInstance($this->getTopicId(), 'modules_website/systemtopic');
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
				return DocumentHelper::getDocumentInstance($brandId, 'modules_brand/brand');
			}
			catch (Exception $e)
			{
				Framework::exception($e);
			}
		}
		return null;
	}
}