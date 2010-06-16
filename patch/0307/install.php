<?php
/**
 * catalog_patch_0307
 * @package modules.catalog
 */
class catalog_patch_0307 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		// Create alert table.
		f_util_System::execChangeCommand('generate-database');
		
		// Add fields in shop table.
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/shop.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'shop');
		$newProp = $newModel->getPropertyByName('enableAvailabilityAlerts');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shop', $newProp);
		$newProp = $newModel->getPropertyByName('availabilityAlertsDuration');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shop', $newProp);
		$newProp = $newModel->getPropertyByName('enablePriceAlerts');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shop', $newProp);
		$newProp = $newModel->getPropertyByName('priceAlertsDuration');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shop', $newProp);
		
		// Initialize shop values.
		foreach (catalog_ShopService::getInstance()->createQuery()->find() as $shop)
		{
			$shop->setEnableAvailabilityAlerts(true);
			$shop->setAvailabilityAlertsDuration('3m');
			$shop->setEnablePriceAlerts(true);
			$shop->setPriceAlertsDuration('3m');
			$shop->save();
		}
		
		// Import notifications.
		$this->executeLocalXmlScript('notifications.xml');
		
		// Add tasks.
		$this->addAlertTasks();
	}
	
	/**
	 * @return void
	 */
	private function addAlertTasks()
	{
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('catalog_PurgeExpiredAlertsTask');
		$task->setLabel('catalog_PurgeExpiredAlertsTask');
		$task->setMinute(0);
		$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'catalog'));
		
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('catalog_SendAlertsTask');
		$task->setLabel('catalog_SendAlertsTask');
		$task->setMinute(0);
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
		return '0307';
	}
}