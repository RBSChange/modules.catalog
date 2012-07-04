<?php
/* @var $arguments array */
$arguments = isset($arguments) ? $arguments : array();
$emailArray = $arguments;
$tm = f_persistentdocument_TransactionManager::getInstance();
try
{
	$tm->beginTransaction();
	$as = catalog_AlertService::getInstance();
	foreach ($emailArray as $email)
	{
		Framework::info(date_Calendar::getInstance()->toString() . " Send alerts for $email ...");
		$as->sendAlertsForEmail($email);
	}
	$tm->commit();
}
catch (Exception $e)
{
	$tm->rollBack($e);
}
echo 'OK';