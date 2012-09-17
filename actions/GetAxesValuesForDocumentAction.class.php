<?php
/**
 * catalog_GetAxesValuesForDocumentAction
 * @package modules.catalog.actions
 */
class catalog_GetAxesValuesForDocumentAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array('linkedAxes' => array());

		$target = DocumentHelper::getCorrection($this->getDocumentInstanceFromRequest($request));
		if ($target instanceof catalog_persistentdocument_declinedproduct)
		{
			$result['linkedAxes'] = catalog_CrossitemService::getInstance()->getAxesInfosByDeclined($target);
		}

		return $this->sendJSON($result);
	}
}