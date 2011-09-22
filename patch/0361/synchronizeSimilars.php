<?php
if (!defined("WEBEDIT_HOME"))
{
	define("WEBEDIT_HOME", realpath('.'));
	require_once WEBEDIT_HOME . "/framework/Framework.php";
	$ids = array_slice($_SERVER['argv'], 1);
}
else
{
	$ids = $_POST['argv'];
}

$rc = RequestContext::getInstance();
foreach ($ids as $id)
{
	$declination = catalog_persistentdocument_productdeclination::getInstanceById($id);
	$rc->beginI18nWork($declination->getLang());
	$declination->setModificationdate(null);
	$declination->save();
	$rc->endI18nWork();
}

echo "OK";