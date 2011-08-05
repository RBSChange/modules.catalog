<?php
$itemIds = $_POST["argv"][0];

$now = date_Calendar::getInstance()->toString();
foreach ($itemIds as $itemId)
{
	$kititem = DocumentHelper::getDocumentInstance($itemId);
	$kititem->setModificationdate($now);
	$kititem->save();
}

echo "OK";