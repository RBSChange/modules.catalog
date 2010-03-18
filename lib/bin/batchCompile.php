<?php
define("WEBEDIT_HOME", realpath('.'));
require_once WEBEDIT_HOME . "/framework/Framework.php";
Controller::newInstance("controller_ChangeController");

$productIdArray = array_slice($_SERVER['argv'], 1);
$tm = f_persistentdocument_TransactionManager::getInstance();
try
{
	$tm->beginTransaction();
	foreach ($productIdArray as $productId)
	{
		echo date_Calendar::getInstance()->toString() . " Compile $productId ...";
		$product = DocumentHelper::getDocumentInstance($productId, 'modules_catalog/product');
		catalog_CompiledproductService::getInstance()->generateForProduct($product);
		echo "Ok\n"; 
	}
	$tm->commit();
}
catch (Exception $e)
{
	$tm->rollBack($e);
}