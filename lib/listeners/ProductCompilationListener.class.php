<?php
/**
 * catalog_ProductCompilationListener
 * @package modules.catalog
 */
class catalog_ProductCompilationListener
{
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
			elseif ($target instanceof catalog_persistentdocument_declinedproduct)
			{
				$cdps = catalog_ProductdeclinationService::getInstance();
				$ids = DocumentHelper::getIdArrayFromDocumentArray($cdps->getArrayByDeclinedProduct($target, false));
				catalog_ProductService::getInstance()->setNeedCompile($ids);
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
			elseif ($target instanceof catalog_persistentdocument_declinedproduct)
			{
				$cdps = catalog_ProductdeclinationService::getInstance();
				$ids = DocumentHelper::getIdArrayFromDocumentArray($cdps->getArrayByDeclinedProduct($target, false));
				catalog_ProductService::getInstance()->setNeedCompile($ids);
			}
		}
	}
}