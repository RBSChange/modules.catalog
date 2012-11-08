<?php
$lastId = $argv[0];
$chunkSize = $argv[1];
$tm = f_persistentdocument_TransactionManager::getInstance();
try
{
	$tm->beginTransaction();
	
	$query = catalog_ProductService::getInstance()->createQuery()->add(Restrictions::gt('id', $lastId));
	$docs = $query->setMaxResults($chunkSize)->find();
	
	echo PHP_EOL, 'id:-1';
	foreach ($docs as $doc)
	{
		/* @var $doc catalog_persistentdocument_product */
		$docId = $doc->getId();
		echo PHP_EOL, 'id:', $docId;
		
		// Trim SKU codes.
		$code = $doc->getCodeSKU();
		if ($code !== trim($code))
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info('[PATCH catalog/0380] Trim SKU for product ' . $docId);
			}
			$doc->setCodeSKU(trim($code));
			$doc->save();
		}
	}
	
	$tm->commit();
}
catch (Exception $e)
{
	echo PHP_EOL, 'EXCEPTION: ', $e->getMessage();
	$tm->rollBack($e);
}