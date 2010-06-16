<?php
class catalog_PurgeExpiredAlertsTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		catalog_AlertService::getInstance()->purgeExpiredAlerts();	
	}
}