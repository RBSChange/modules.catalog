<?php
/**
 * catalog_patch_0304
 * @package modules.catalog
 */
class catalog_patch_0304 extends patch_BasePatch
{

	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->log("Ajout de la nouvelle propriété 'compiled'...");
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/product.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'product');
		$newProp = $newModel->getPropertyByName('compiled');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'product', $newProp);
		
		$this->log("Ajout de la nouvelle propriété 'indexed'...");
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/compiledproduct.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'compiledproduct');
		$newProp = $newModel->getPropertyByName('indexed');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'compiledproduct', $newProp);
		
		$this->log("Ajout de la tache de compilation en tache de fond...");
		$this->addBackGroundCompileTask();	

		$this->log("Actualisation des produits compilés...");
		exec("change.php catalog.compile-catalog");
		
		$this->log("Mise à jour du statut de compilation des produits non compilable...");
		$this->executeSQLQuery("UPDATE m_catalog_doc_product SET compiled = 1");
		
	}

	
	private function addBackGroundCompileTask()
	{
		$sendMailsTask = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$sendMailsTask->setSystemtaskclassname('catalog_BackgroundCompileTask');
		//$sendMailsTask->setMinute(null);
		$sendMailsTask->setLabel('catalog_BackgroundCompileTask');
		$sendMailsTask->save(ModuleService::getInstance()->getSystemFolderId('task', 'catalog'));
	}
	
	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'catalog';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0304';
	}
}