<?php
$arguments = isset($arguments) ? $arguments : array();
$lastId = $arguments[0];
$chunkSize = $arguments[1];
$tm = f_persistentdocument_TransactionManager::getInstance();
try
{
	$tm->beginTransaction();
	echo 'id:-1';
	
	$cccis = catalog_CompiledcrossitemService::getInstance();
	$crossitems = catalog_CrossitemService::getInstance()->getNextGeneratedItems($lastId, $chunkSize);
	foreach ($crossitems as $crossitem)
	{
		$crossId = $crossitem->getId();
		if (Framework::isInfoEnabled())
		{
			Framework::info(date_Calendar::getInstance()->toString() . ' Delete generated crossitem ' . $crossId . '...');
		}
		echo PHP_EOL, 'id:', $crossId;
		$crossitem->delete();
	}
	$tm->commit();
}
catch (Exception $e)
{
	echo PHP_EOL, 'EXCEPTION: ', $e->getMessage(); 
	$tm->rollBack($e);
}