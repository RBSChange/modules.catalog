<?php
/**
 * catalog_persistentdocument_compiledproduct
 * @package modules.catalog
 */
class catalog_persistentdocument_compiledproduct extends catalog_persistentdocument_compiledproductbase
{	
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
	
	/**
	 * @var integer
	 */
	private $popinPageId = false;
	
	/**
	 * @return integer
	 */
	public function getPopinPageId()
	{
		if ($this->popinPageId === false)
		{
			$query = website_PageService::getInstance()->createQuery();
			$query->add(Restrictions::childOf($this->getTopicId()));
			$query->add(Restrictions::hasTag('functional_catalog_product-popin'));
			$page = $query->findUnique();
			$this->popinPageId = ($page && $page->isPublished()) ? $page->getId() : null;
		}
		return $this->popinPageId;
	}
}