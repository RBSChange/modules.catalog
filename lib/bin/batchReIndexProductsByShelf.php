<?php
$shelfId = $argv[0];
$firstId = $argv[1];
$limit = $argv[2];

try
{
	echo 'id:-1';
	
	$shelfIds = catalog_ShelfService::getInstance()->createQuery()->add(Restrictions::descendentOf($shelfId))
		->setProjection(Projections::property('id'))->findColumn('id');
	$shelfIds[] = $shelfId;
	
	$is = indexer_IndexService::getInstance();
	$query = catalog_CompiledproductService::getInstance()->createQuery()
		->add(Restrictions::in('shelfId', $shelfIds))
		->add(Restrictions::gt('id', $firstId))
		->addOrder(Order::asc('id'))
		->setMaxResults($limit);
	foreach ($query->find() as $doc)
	{
		$is->update($doc);
		echo PHP_EOL, 'id:', $doc->getId();
	}
}
catch (Exception $e)
{
	echo PHP_EOL, 'EXCEPTION: ', $e->getMessage();
}