<?php
$arguments = isset($arguments) ? $arguments : array();
$linkType = $arguments[0];
$feederClass = $arguments[1];
$maxCount = $arguments[2];
$type = $arguments[3];
$lastId = $arguments[4];
$chunkSize = $arguments[5];
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