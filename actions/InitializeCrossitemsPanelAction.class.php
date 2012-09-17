<?php
/**
 * catalog_InitializeCrossitemsPanelAction
 * @package modules.catalog.actions
 */
class catalog_InitializeCrossitemsPanelAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();
		
		$ccis = catalog_CrossitemService::getInstance();
		$target = DocumentHelper::getCorrection($this->getDocumentInstanceFromRequest($request));
		if ($ccis->isValidForTargetOrLinkendDocument($target))
		{
			$result = catalog_CrossitemService::getInstance()->getCrossitemsInfosByTarget($target);
		}
		
		return $this->sendJSON($result);
	}
}