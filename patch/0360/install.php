<?php
/**
 * catalog_patch_0360
 * @package modules.catalog
 */
class catalog_patch_0360 extends patch_BasePatch
{ 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->log('Add codeSKU field on product document ...');
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/product.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'product');
		$newProp = $newModel->getPropertyByName('codeSKU');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'product', $newProp);
		
		$this->log('Update codereference db-size to 50 ...');
		$sql = "ALTER TABLE `m_catalog_doc_product` CHANGE `codereference` `codereference` VARCHAR( 50 ) NULL DEFAULT NULL";
		
		
		$this->log('Compiles locales ...');
		$this->execChangeCommand('compile-locales', array('catalog'));
		
		$this->log('Compiles editors ...');
		$this->execChangeCommand('compile-editors-config');
	}
}