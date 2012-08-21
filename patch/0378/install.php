<?php
/**
 * catalog_patch_0378
 * @package modules.catalog
 */
class catalog_patch_0378 extends patch_BasePatch
{
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->addWebsiteSynchroTask();
	}
	
	/**
	 * @return void
	 */
	protected function addWebsiteSynchroTask()
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