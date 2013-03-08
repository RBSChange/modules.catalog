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
			$userIds = $this->getIndexFrontendAccessorIds();
			if (count($userIds))
			{
				$product = $this->getProduct();
				$indexDocument = $product->getIndexedDocumentByCompiledProduct($this);
				if ($indexDocument !== null)
				{
					$indexDocument->setId($this->getId());
					$indexDocument->setDocumentModel("modules_catalog/compiledproduct");	
					$indexDocument->setDocumentAccessors($userIds);			
					$indexDocument->setIntegerField('primary', $this->getPrimary() ? 1 : 0);
					if ($this->getDocumentService()->indexFacets($this, $indexDocument))
					{
						return $indexDocument;
					}				
				}
			}
		}
		return null;
	}
	
	/**
	 * @return Integer[]
	 */
	private function getIndexFrontendAccessorIds()
	{
		$page = $this->getDocumentService()->getDisplayPage($this);
		if ($page === null)
		{
			return array();
		}
		
		$accessorIds = f_permission_PermissionService::getInstance()->getAccessorIdsForRoleByDocumentId('modules_website.AuthenticatedFrontUser', $page->getId());
		if (count($accessorIds))
		{
			return $accessorIds;
		}
		return array(0);
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