<?php
/* @var $arguments array */
$arguments = isset($arguments) ? $arguments : array();

$productIdArray = $arguments;
Framework::info(__FILE__ . ' -> ' . implode(', ', $productIdArray));
$tm = f_persistentdocument_TransactionManager::getInstance();
try
{
	$tm->beginTransaction();
	foreach ($productIdArray as $productId)
	{
		Framework::info(date_Calendar::getInstance()->toString() . " Compile $productId ...");
		$product = DocumentHelper::getDocumentInstance($productId, 'modules_catalog/product');
		
		catalog_CompiledproductService::getInstance()->generateForProduct($product);
	}
	$tm->commit();
}
catch (Exception $e)
{
	$tm->rollBack($e);
}
echo 'OK';