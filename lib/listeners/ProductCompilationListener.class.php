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
		$cps = catalog_CompiledproductService::getInstance();
		$document = $params['document'];
		if ($document instanceof brand_persistentdocument_brand)
		{
			if (in_array('label', $params['modifiedPropertyNames']))
			{
				$cps->compileBrandInfos($document->getId());
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
			$modelName = $document->getTargetdocumentmodel();
			$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($document->getTargetdocumentmodel());
			if ($modelName == 'modules_catalog/product' || $model->isModelCompatible('modules_catalog/product'))
			{
				catalog_CompiledproductService::getInstance()->compileRatingInfos($document->getTarget());
			}
		}
	}
}