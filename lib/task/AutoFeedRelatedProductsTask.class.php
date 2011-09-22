<?php
class catalog_AutoFeedRelatedProductsTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$ms = ModuleService::getInstance();
		
		$feeder = $ms->getPreferenceValue('catalog', 'suggestComplementaryFeederClass');
		$complementaryMaxCount = ($feeder && $feeder != 'none') ? $ms->getPreferenceValue('catalog', 'autoFeedComplementaryMaxCount') : 0;
		$feeder = $ms->getPreferenceValue('catalog', 'suggestSimilarFeederClass');
		$similarMaxCount = ($feeder && $feeder != 'none') ? $ms->getPreferenceValue('catalog', 'autoFeedSimilarMaxCount') : 0;
		$feeder = $ms->getPreferenceValue('catalog', 'suggestUpsellFeederClass');
		$upsellMaxCount = ($feeder && $feeder != 'none') ? $ms->getPreferenceValue('catalog', 'autoFeedUpsellMaxCount') : 0;
		if (($complementaryMaxCount < 1) && ($similarMaxCount < 1) && ($upsellMaxCount < 1))
		{
			$this->reSchedule();
			return;
		}
		
		$errors = array();
		
		$ids = catalog_ProductService::getInstance()->getAllProductIdsToFeedRelated();
		$batchPath = 'modules/catalog/lib/bin/batchAutoFeedRelatedProducts.php';
		foreach (array_chunk($ids, 10) as $chunk)
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
		else
		{		
			$this->reSchedule();
		}
	}

	private function reSchedule()
	{
		$date = date_Calendar::getInstance();
		$duration = ModuleService::getInstance()->getPreferenceValue('catalog', 'autoFeedDelay');
		$durationValue = intval(substr($duration, 0, -1));
		switch (substr($duration, -1, 1))
		{
			case 'h':
				$date->add(date_Calendar::HOUR, $durationValue);
				break;
			case 'd':
				$date->add(date_Calendar::DAY, $durationValue);
				break;
			case 'w':
				$date->add(date_Calendar::DAY, $durationValue * 7);
				break;
			case 'm':
				$date->add(date_Calendar::MONTH, $durationValue);
				break;
			case 'y':
				$date->add(date_Calendar::YEAR, $durationValue);
				break;
			default:
				break;
		}
		$this->plannedTask->reSchedule($date);
	}
}