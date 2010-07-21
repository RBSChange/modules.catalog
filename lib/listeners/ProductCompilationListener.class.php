<?php
/**
 * catalog_ProductCompilationListener
 * @package modules.catalog
 */
class catalog_ProductCompilationListener
{
	/**
	 * @param f_persistentdocument_PersistentDocument $sender
	 * @param array $params
	 * @return void
	 */
	public function onPersistentDocumentUpdated($sender, $params)
	{
		$document = $params['document'];
		if ($document instanceof brand_persistentdocument_brand)
		{
			if (in_array('label', $params['modifiedPropertyNames']))
			{
				catalog_ProductService::getInstance()->setNeedCompileForBrand($document);
			}
		}
	}
	
	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentPublished($sender, $params)
	{
		$document = $params['document'];
		if ($document instanceof comment_persistentdocument_comment)
		{
			$target = $document->getTarget();
			if ($target instanceof catalog_persistentdocument_product)
			{
				catalog_ProductService::getInstance()->setNeedCompile(array($target->getId()));
			}
		}
	}
	
	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentUnpublished($sender, $params)
	{
		$document = $params['document'];
		if ($document instanceof comment_persistentdocument_comment)
		{
			$target = $document->getTarget();
			if ($target instanceof catalog_persistentdocument_product)
			{
				catalog_ProductService::getInstance()->setNeedCompile(array($target->getId()));
			}
		}
	}
}