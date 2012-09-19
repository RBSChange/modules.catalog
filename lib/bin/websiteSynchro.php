<?php
/* @var $arguments array */
$arguments = isset($arguments) ? $arguments : array();
echo "start:" . f_util_ArrayUtils::firstElement($arguments);
$tm = f_persistentdocument_TransactionManager::getInstance();
foreach ($arguments as $id)
{
	$toCheckIds = null;
	try
	{
		$tm->beginTransaction();
		$doc = DocumentHelper::getDocumentInstanceIfExists($id);
		if (($doc instanceof catalog_persistentdocument_shop) || ($doc instanceof catalog_persistentdocument_shelf))
		{
			$toCheckIds = $doc->getDocumentService()->websiteSynchroCheck($doc);
		}
		
		if (is_array($toCheckIds) && count($toCheckIds))
		{
			foreach ($toCheckIds as $idToCheck)
			{
				echo PHP_EOL, 'id:', $idToCheck;
			}
		}
		$tm->commit();
	}
	catch (Exception $e)
	{
		echo PHP_EOL, 'EXCEPTION: ', $e->getMessage();
		$tm->rollBack($e);
	}
}
echo PHP_EOL, "end:" . $id;