<?php
echo "id:-1";
$lastId = $argv[0];
$chunkSize = $argv[1];
$compileAll = $argv[2] == 1;

$tm = f_persistentdocument_TransactionManager::getInstance();
$ids = catalog_ProductService::getInstance()->getProductIdsToCompile($lastId, $chunkSize, $compileAll);

foreach (array_chunk($ids, 10) as $chunk)
{
	try
	{
		$tm->beginTransaction();
		foreach ($chunk as $id)
		{
			echo PHP_EOL, 'id:', $id;
			catalog_CompiledproductService::getInstance()->generateForProduct(catalog_persistentdocument_product::getInstanceById($id));
		}
		$tm->commit();
	}
	catch (Exception $e)
	{
		echo PHP_EOL, 'EXCEPTION: ', $e->getMessage();
		$tm->rollBack($e);
	}
}