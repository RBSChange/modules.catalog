<?php
/**
 * catalog_ShopScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_ShopScriptDocumentElement extends import_ScriptDocumentElement
{
	
	/**
	 * @param import_ScriptReader $script
	 * @param import_ScriptBaseElement $parentElement
	 * @param string $name
	 */
	public function __construct($script, $parentElement, $name)
	{
		parent::__construct($script, $parentElement, $name);
		change_ConfigurationService::getInstance()->addVolatileProjectConfigurationNamedEntry('modules/catalog/useAsyncWebsiteUpdate', 'false');
	}
	
	/**
	 * @return catalog_persistentdocument_shop
	 */
	protected function initPersistentDocument()
	{
		$document = catalog_ShopService::getInstance()->getNewDocumentInstance();
		$parentDocument = $this->getParentDocument();
		if ($parentDocument instanceof import_ScriptDocumentElement)
		{
			$mountParent = $this->getParentDocument()->getPersistentDocument();
			if ($mountParent instanceof website_persistentdocument_website || $mountParent instanceof website_persistentdocument_topic)
			{
				$document->setMountParentId($mountParent->getId());
			}
		}
		return $document;
	}
	
	protected function getDocumentProperties()
	{
		$shop = $this->getPersistentDocument();
		if ($shop->isNew())
		{
			$mountParent = $this->getComputedAttribute('mountParent');
			if ($mountParent instanceof website_persistentdocument_website || $mountParent instanceof website_persistentdocument_topic)
			{
				$shop->setMountParentId($mountParent->getId());
			}
		}
		
		return parent::getDocumentProperties();
	}
	
	/**
	 * @see DocumentService::save($parentId)
	 * @return integer id of the document has to be the parent document
	 */
	protected function getParentNodeId()
	{
		return catalog_ShopfolderService::getInstance()->getShopFolder()->getId();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/shop');
	}
}