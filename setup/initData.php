<?php
class catalog_Setup extends object_InitDataSetup
{
	public function install()
	{
		try
		{
			$scriptReader = import_ScriptReader::getInstance();
			$scriptReader->executeModuleScript('catalog', 'init.xml');
			$scriptReader->executeModuleScript('catalog', 'currencies.xml');
			$scriptReader->executeModuleScript('catalog', 'axes.xml');	
			$scriptReader->executeModuleScript('catalog', 'list-taxcategory.xml');
			$scriptReader->executeModuleScript('catalog', 'list-billingareabyshop.xml');
			$scriptReader->executeModuleScript('catalog', 'order-process.xml');
			$scriptReader->executeModuleScript('catalog', 'comparableproperty.xml');
		}
		catch (Exception $e)
		{
			echo "ERROR: " . $e->getMessage() . "\n";
			Framework::exception($e);
		}
		$this->addBackGroundCompileTask();
		$this->addAlertTasks();
		$this->addRelatedProductAutoFeedTask();
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
	private function addRelatedProductAutoFeedTask()
	{
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('catalog_AutoFeedRelatedProductsTask');
		$task->setLabel('catalog_AutoFeedRelatedProductsTask');
		$task->setUniqueExecutiondate(date_Calendar::getInstance());
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