<?php
/* @var $arguments array */
$arguments = isset($arguments) ? $arguments : array();
$productIdArray = $arguments;

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