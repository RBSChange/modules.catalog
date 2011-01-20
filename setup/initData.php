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
			$scriptReader->executeModuleScript('catalog', 'taxes.xml');
			$scriptReader->executeModuleScript('catalog', 'axes.xml');
		}
		catch (Exception $e)
		{
			echo "ERROR: " . $e->getMessage() . "\n";
			Framework::exception($e);
		}
		$this->addBackGroundCompileTask();
		$this->addAlertTasks();
		$this->addRelatedProductAutoFeedTask();
		
	/*
		$query = array();
		$query[] ="ALTER TABLE `m_catalog_doc_price` CHANGE `productid` `productid` INT( 11 ) NOT NULL";
		$query[] ="ALTER TABLE `m_catalog_doc_price` CHANGE `shopid` `shopid` INT( 11 ) NOT NULL";
		$query[] ="ALTER TABLE `m_catalog_doc_price` CHANGE `thresholdmin` `thresholdmin` INT( 11 ) NOT NULL";
		$query[] ="ALTER TABLE `m_catalog_doc_price` CHANGE `targetid` `targetid` INT( 11 ) NOT NULL";
		$query[] ="ALTER TABLE `m_catalog_doc_price` CHANGE `priority` `priority` INT( 11 ) NOT NULL";
		$query[] ="ALTER TABLE `m_catalog_doc_price` CHANGE `document_publicationstatus` `document_publicationstatus` enum('DRAFT','CORRECTION','ACTIVE','PUBLICATED','DEACTIVATED','FILED','DEPRECATED','TRASH','WORKFLOW') NOT NULL";
		$query[] ="ALTER TABLE `m_catalog_doc_price` ADD INDEX `GETPRICE` ( `productid` , `shopid` , `thresholdmin` , `targetid` , `priority` , `document_publicationstatus` )";
		foreach ($query as $sql) 
		{
			$this->getPersistentProvider()->executeSQLScript($sql);
		}
		*/
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
}