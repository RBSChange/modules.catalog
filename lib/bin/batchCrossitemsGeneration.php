<?php
$linkType = $argv[0];
$feederClass = $argv[1];
$maxCount = $argv[2];
$type = $argv[3];
$lastId = $argv[4];
$chunkSize = $argv[5];
$tm = f_persistentdocument_TransactionManager::getInstance();
try
{
	$tm->beginTransaction();
	echo 'id:-1';
	
	$ccis = catalog_CrossitemService::getInstance();
	foreach (catalog_CrossitemService::getInstance()->getNextTargetsToGenerate($lastId, $chunkSize, $type) as $product)
	{
		$productId = $product->getId();
		if (Framework::isInfoEnabled())
		{
			Framework::info(date_Calendar::getInstance()->toString() . ' Generate crossitems for ' . $productId . '...');
		}
		echo PHP_EOL, 'id:', $productId;
		$ccis->refreshGeneratedItemsOnTarget($product, $linkType, $feederClass, $maxCount);
	}
	$tm->commit();
}
catch (Exception $e)
{
	echo PHP_EOL, 'EXCEPTION: ', $e->getMessage(); 
	$tm->rollBack($e);
}