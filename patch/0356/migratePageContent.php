<?php
$lang = $_POST['argv'][0];
$pageIds = $_POST['argv'][1];

$tm = f_persistentdocument_TransactionManager::getInstance();
$pp = f_persistentdocument_PersistentProvider::getInstance();
$rc = RequestContext::getInstance();

$xslP = new XSLTProcessor();
$xsl = new DOMDocument();
$xsl->load(f_util_FileUtils::buildWebeditPath("modules/catalog/patch/0356/migratePageContent.xsl"));
$xslP->importStyleSheet($xsl);

try
{
	$tm->beginTransaction();
	$rc->beginI18nWork($lang);
	$query = website_PageService::getInstance()->createQuery()
		->add(Restrictions::in("id", $pageIds));
	$pages = $query->find();
	$updatedCount = 0;
	foreach ($pages as $page)
	{
		$contentDoc = new DOMDocument();
		$contentDoc->loadXML($page->getContent());
		$newContent = $xslP->transformToXML($contentDoc);
		$page->setContent($newContent);
		if ($page->isModified())
		{
			$pp->updateDocument($page);
			$updatedCount++;
		}
	}
	$tm->commit();
	$rc->endI18nWork();
	echo $updatedCount;
}
catch (Exception $e)
{
	$tm->rollBack($e);
	echo "ERROR: " . $e->getMessage();
}