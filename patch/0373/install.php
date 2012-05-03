<?php
/**
 * catalog_patch_0373
 * @package modules.catalog
 */
class catalog_patch_0373 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		foreach (catalog_AttributefolderService::getInstance()->createQuery()->find() as $folder)
		{
			/* @var $folder generic_persistentdocument_folder */
			$folder->setLabel('m.catalog.document.attributefolder.document-name');
			$folder->save();
		}
		
		foreach (catalog_ComparablepropertyfolderService::getInstance()->createQuery()->find() as $folder)
		{
			/* @var $folder generic_persistentdocument_folder */
			$folder->setLabel('m.catalog.document.comparablepropertyfolder.document-name');
			$folder->save();
		}
		
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/product.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'product');
		$newProp = $newModel->getPropertyByName('minOrderQuantity');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'product', $newProp);
		
		$this->executeSQLQuery('UPDATE m_catalog_doc_product SET minorderquantity = 1 WHERE minorderquantity IS NULL;');
	}
}