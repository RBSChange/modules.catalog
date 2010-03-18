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
	}
	
	private function addBackGroundCompileTask()
	{
		$sendMailsTask = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$sendMailsTask->setSystemtaskclassname('catalog_BackgroundCompileTask');
		//$sendMailsTask->setMinute(null);
		$sendMailsTask->setLabel('catalog_BackgroundCompileTask');
		$sendMailsTask->save(ModuleService::getInstance()->getSystemFolderId('task', 'catalog'));
	}
}