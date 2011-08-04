<?php
if (!defined("PROJECT_HOME"))
{
	define("PROJECT_HOME", realpath('.'));
	require_once PROJECT_HOME . "/framework/Framework.php";
	$emailArray = array_slice($_SERVER['argv'], 1);
}
else
{
	$emailArray = $_POST['argv'];
}

change_Controller::newInstance('change_Controller');
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