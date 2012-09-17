<?php
/**
 * catalog_LoadCrossitemSuggestionsAction
 * @package modules.catalog.actions
 */
class catalog_LoadCrossitemSuggestionsAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$target = $this->getDocumentInstanceFromRequest($request);
		$excludedIds = explode(',', $request->getParameter('excludedIds'));
		$feederClass = $request->getParameter('feederClass');
		$maxCount = $request->getParameter('maxResults');
		$result['nodes'] = catalog_CrossitemService::getInstance()->getSuggestionInfos($target, $excludedIds, $feederClass, $maxCount);
				
		return $this->sendJSON($result);
	}
}