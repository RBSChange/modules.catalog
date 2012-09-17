<?php
$lastId = $argv[0];
$chunkSize = $argv[1];
$onlyNotCompiled = $argv[2] == 'true';
$tm = f_persistentdocument_TransactionManager::getInstance();
try
{
	$tm->beginTransaction();
	echo 'id:-1';
	
	$cccis = catalog_CompiledcrossitemService::getInstance();
	$crossitems = catalog_CrossitemService::getInstance()->getNextToCompile($lastId, $chunkSize, $onlyNotCompiled);
	foreach ($crossitems as $crossitem)
	{
		$crossId = $crossitem->getId();
		if (Framework::isInfoEnabled())
		{
			Framework::info(date_Calendar::getInstance()->toString() . ' Compile crossitem ' . $crossId . '...');
		}
		echo PHP_EOL, 'id:', $crossId;
		$cccis->generateForCrossitem($crossitem);
	}
	$tm->commit();
}
catch (Exception $e)
{
	echo PHP_EOL, 'EXCEPTION: ', $e->getMessage(); 
	$tm->rollBack($e);
}