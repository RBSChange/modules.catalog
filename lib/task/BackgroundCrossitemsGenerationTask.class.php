<?php
class catalog_BackgroundCrossitemsGenerationTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$batchPath = 'modules/catalog/lib/bin/batchCrossitemsGeneration.php';
		$errors = array();
		
		$chunkSize = Framework::getConfigurationValue('modules/catalog/crossitemsGenerationChunkSize', 25);
		$genConfig = Framework::getConfigurationValue('modules/catalog/crossitemsGeneration', array());
		$errors = array();
		foreach ($genConfig as $linkType => $config)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info(date_Calendar::getInstance()->toString() . ' Start crossitems generation for ' . $linkType);
			}
			
			if (!isset($config['maxCount']) || intval($config['maxCount']) < 1)
			{
				if (Framework::isInfoEnabled())
				{
					Framework::info(date_Calendar::getInstance()->toString() . ' Cancel crossitems generation for ' . $linkType . ' (no strictly positive maxCount)');
				}
				continue;
			}
			$maxCount = intval($config['maxCount']);
			
			if (!isset($config['feederClass']) || !class_exists($config['feederClass']))
			{
				if (Framework::isInfoEnabled())
				{
					Framework::info(date_Calendar::getInstance()->toString() . ' Cancel crossitems generation for ' . $linkType . ' (no valid feederClass)');
				}
				continue;
			}
			$feederClass = $config['feederClass'];
			
			foreach (array('product', 'declined') as $type)
			{
				$id = 0;
				while ($id >= 0)
				{
					$this->plannedTask->ping();
					$result = f_util_System::execScript($batchPath, array($linkType, $feederClass, $maxCount, $type, $id, $chunkSize));
					
					$lines = array_reverse(explode(PHP_EOL, $result));
					foreach ($lines as $line)
					{
						if (preg_match('/^id:([0-9-]+)$/', $line, $matches))
						{
							$id = $matches[1];
							break;
						}
						else
						{
							$errors[] = $line;
						}
					}
				}
			}
			
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' End crossitems generation for ' . $linkType);
			}
		}
		
		if (count($errors))
		{
			throw new Exception(implode(PHP_EOL, array_reverse($errors)));
		}
		else
		{
			$this->reSchedule();
		}
	}
	
	private function reSchedule()
	{
		$date = date_Calendar::getInstance();
		$duration = Framework::getConfigurationValue('modules/catalog/crossitemsGenerationDelay', '1d');
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