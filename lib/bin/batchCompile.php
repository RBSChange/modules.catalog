<?php
if (!defined("WEBEDIT_HOME"))
{
	define("WEBEDIT_HOME", realpath('.'));
	require_once WEBEDIT_HOME . "/framework/Framework.php";
	$productIdArray = array_slice($_SERVER['argv'], 1);
}
else
{
	$productIdArray = $_POST['argv'];
}

Controller::newInstance("controller_ChangeController");
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