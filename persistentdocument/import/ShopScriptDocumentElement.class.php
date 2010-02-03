<?php
/**
 * catalog_ShopScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_ShopScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_shop
     */
    protected function initPersistentDocument()
    {
    	$document = catalog_ShopService::getInstance()->getNewDocumentInstance();
    	$mountParent = $this->getParentDocument()->getPersistentDocument();
    	$document->setMountParentId($mountParent->getId());
    	return $document;
    }
    
	/**
	 * @see DocumentService::save($parentId)
	 * @return Integer id of the document has to be the parent document
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