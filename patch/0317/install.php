<?php
/**
 * catalog_patch_0317
 * @package modules.catalog
 */
class catalog_patch_0317 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/preferences.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'preferences');
		$newProp = $newModel->getPropertyByName('autoFeedComplementaryMaxCount');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'preferences', $newProp);
		$newProp = $newModel->getPropertyByName('autoFeedSimilarMaxCount');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'preferences', $newProp);
		$newProp = $newModel->getPropertyByName('autoFeedUpsellMaxCount');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'preferences', $newProp);
		$newProp = $newModel->getPropertyByName('autoFeedDelay');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'preferences', $newProp);
		
		$ms = ModuleService::getInstance();
		$prefs = $ms->getPreferencesDocument('catalog');
		$prefs->setAutoFeedDelay('1d');
		$prefs->save();
		
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('catalog_AutoFeedRelatedProductsTask');
		$task->setLabel('catalog_AutoFeedRelatedProductsTask');
		$task->setUniqueExecutiondate(date_Calendar::getInstance());
		$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'catalog'));
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
		return '0317';
	}
}