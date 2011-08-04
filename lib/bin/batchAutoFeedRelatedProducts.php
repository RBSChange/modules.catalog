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
change_Controller::newInstance('change_Controller');
$tm = f_persistentdocument_TransactionManager::getInstance();
try
{
	$tm->beginTransaction();
	foreach ($productIdArray as $productId)
	{
		Framework::info(date_Calendar::getInstance()->toString() . " Update related products for $productId ...");
		$product = DocumentHelper::getDocumentInstance($productId, 'modules_catalog/product');
		if ($product->handleRelatedProducts())
		{
			$product->getDocumentService()->feedRelatedProducts($product);
		}
	}
	$tm->commit();
}
catch (Exception $e)
{
	$tm->rollBack($e);
	throw $e;
}
echo 'OK';