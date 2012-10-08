<?php
class catalog_Setup extends object_InitDataSetup
{
	public function install()
	{
		$this->executeModuleScript('init.xml');
		$this->executeModuleScript('currencies.xml');
		$this->executeModuleScript('comparableproperty.xml');

		$this->addBackGroundCompileTask();
		$this->addAlertTasks();
		$this->addWebsiteSynchroTask();
	}
	
	/**
	 * @return void
	 */
	private function addBackGroundCompileTask()
	{
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('catalog_BackgroundCompileTask');
		$task->setLabel('catalog_BackgroundCompileTask');
		$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'catalog'));
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
	 * @return void
	 */
	private function addWebsiteSynchroTask()
	{
		$tasks = task_PlannedtaskService::getInstance()->getBySystemtaskclassname('catalog_WebsiteSynchroTask');
		if (count($tasks) === 0)
		{
			$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
			$task->setSystemtaskclassname('catalog_WebsiteSynchroTask');
			$task->setLabel('catalog_WebsiteSynchroTask');
			$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'catalog'));
		}
	}
}