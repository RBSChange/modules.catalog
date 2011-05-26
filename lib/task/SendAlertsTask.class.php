<?php
class catalog_SendAlertsTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$emails = catalog_AlertService::getInstance()->getPendingEmails();
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' emails to send: ' . count($emails));			
		}
		$errors = array();
		$batchPath = 'modules/catalog/lib/bin/batchSendAlerts.php';
		foreach (array_chunk($emails, 10) as $chunk)
		{
			$this->plannedTask->ping();
			$result = f_util_System::execHTTPScript($batchPath, $chunk);
			// Log fatal errors...
			if ($result != 'OK')
			{
				$errors[] = $result;
			}
		}
		if (count($errors))
		{
			throw new Exception(implode("\n", $errors));
		}
	}
}