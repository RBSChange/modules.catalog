<?php
$lastId = $argv[0];
$chunkSize = $argv[1];
$tm = f_persistentdocument_TransactionManager::getInstance();
try
{
	$tm->beginTransaction();
	
	$query = catalog_KititemService::getInstance()->createQuery()->add(Restrictions::gt('id', $lastId));
	$docs = $query->setMaxResults($chunkSize)->find();
	echo PHP_EOL, 'id:-1';
	foreach ($docs as $doc)
	{
		/* @var $doc catalog_persistentdocument_kititem */
		$docId = $doc->getId();
		echo PHP_EOL, 'id:', $docId;
		
		// Publish if pssible.
		if (Framework::isInfoEnabled())
		{
			Framework::info('[PATCH catalog/0382] Publish kititem if possible ' . $docId);
		}
		$doc->getDocumentService()->publishIfPossible($doc->getId());
	}
	
	$tm->commit();
}
catch (Exception $e)
{
	echo PHP_EOL, 'EXCEPTION: ', $e->getMessage();
	$tm->rollBack($e);
}