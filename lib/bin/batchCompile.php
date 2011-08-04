<?php
if (!defined("PROJECT_HOME"))
{
	define("PROJECT_HOME", realpath('.'));
	require_once PROJECT_HOME . "/framework/Framework.php";
	$productIdArray = array_slice($_SERVER['argv'], 1);
}
else
{
	$productIdArray = $_POST['argv'];
}
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