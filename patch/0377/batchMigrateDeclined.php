<?php
$lastId = $argv[0];
$chunkSize = $argv[1];
$tm = f_persistentdocument_TransactionManager::getInstance();
try
{
	$tm->beginTransaction();
	
	$query = catalog_DeclinedproductService::getInstance()->createQuery()->add(Restrictions::gt('id', $lastId));
	$query->add(Restrictions::orExp(
		Restrictions::isNotNull('complementary'), 
		Restrictions::isNotNull('similar'), 
		Restrictions::isNotNull('upsell')
	));
	$docs = $query->setMaxResults($chunkSize)->find();
	
	echo PHP_EOL, 'id:-1';
	$ccis = catalog_CrossitemService::getInstance();
	foreach ($docs as $doc)
	{
		/* @var $doc catalog_persistentdocument_declinedproduct */
		$docId = $doc->getId();
		if (Framework::isInfoEnabled())
		{
			Framework::info(date_Calendar::getInstance()->toString() . ' Migrate cross selling in declined product ' . $docId . '...');
		}
		echo PHP_EOL, 'id:', $docId;
		
		$ids = DocumentHelper::getIdArrayFromDocumentArray($doc->getComplementaryArray());
		$ccis->createNewCrossitems($doc, $ids, 'complementary');
		$ids = DocumentHelper::getIdArrayFromDocumentArray($doc->getSimilarArray());
		$ccis->createNewCrossitems($doc, $ids, 'similar');
		$ids = DocumentHelper::getIdArrayFromDocumentArray($doc->getUpsellArray());
		$ccis->createNewCrossitems($doc, $ids, 'upsell');
		
		$doc->removeAllComplementary();
		$doc->removeAllSimilar();
		$doc->removeAllUpsell();
		$doc->save();
	}
	
	$tm->commit();
}
catch (Exception $e)
{
	echo PHP_EOL, 'EXCEPTION: ', $e->getMessage(); 
	$tm->rollBack($e);
}