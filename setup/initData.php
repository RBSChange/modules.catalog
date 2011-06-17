<?php
class catalog_Setup extends object_InitDataSetup
{
	public function install()
	{
		try
		{
			$scriptReader = import_ScriptReader::getInstance();
       	 	$scriptReader->executeModuleScript('catalog', 'init.xml');
		}
		catch (Exception $e)
		{
			echo "ERROR: " . $e->getMessage() . "\n";
			Framework::exception($e);
		}		
		$this->addBackGroundCompileTask();
		$this->addAlertTasks();
		$this->addRelatedProductAutoFeedTask();
		$this->tempFunctionToRemoveIn350();
	}
	
	private function tempFunctionToRemoveIn350()
	{
		// See FIX #32733
		$newNoShelfFolder = f_util_FileUtils::buildWebeditPath("modules/catalog/patch/0319/noshelffolder.png");
		$oldNoShelfFolder = f_util_FileUtils::buildWebeditPath("libs/icons/small/noshelffolder.png");
		try
		{
			f_util_FileUtils::cp($newNoShelfFolder, $oldNoShelfFolder, f_util_FileUtils::OVERRIDE);
		}
		catch (Exception $e)
		{
			$this->logWarning("Could not create libs/icons/small/noshelffolder.png please do it manually using ".$newNoShelfFolder);
		}
		
		// See FIX #35085
		$newDeclinedProduct = f_util_FileUtils::buildWebeditPath("modules/catalog/patch/0319/declinedproduct.png");
		$oldDeclinedProduct = f_util_FileUtils::buildWebeditPath("libs/icons/small/declinedproduct.png");
		try
		{
			f_util_FileUtils::cp($newDeclinedProduct, $oldDeclinedProduct, f_util_FileUtils::OVERRIDE);
		}
		catch (Exception $e)
		{
			$this->logWarning("Could not create libs/icons/small/declinedproduct.png please do it manually using ".$newDeclinedProduct);
		}
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