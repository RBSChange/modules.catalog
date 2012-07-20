<?php
/**
 * catalog_AddCrossitemsAction
 * @package modules.catalog.actions
 */
class catalog_AddCrossitemsAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$ls = LocaleService::getInstance();
		$ccis = catalog_CrossitemService::getInstance();
		$target = $this->getDocumentInstanceFromRequest($request);
		if (!$ccis->isValidForTargetOrLinkendDocument($target))
		{
			$this->sendJSONError($ls->transBO('m.catalog.bo.doceditor.crossitems.invalid-target', array('ucf')));
		}
		$linkType = $request->getParameter('linkType');
		if (!$linkType)
		{
			$this->sendJSONError($ls->transBO('m.catalog.bo.doceditor.crossitems.invalid-link-type', array('ucf')));
		}
		$linkedIds = explode(',', $request->getParameter('linkedIds'));
		if (count($linkedIds))
		{
			$ccis->createNewCrossitems($target, $linkedIds, $linkType);
			$result['successMessage'] = $ls->transBO('m.catalog.bo.doceditor.crossitems.crossitems-successfuly-created', array('ucf'));
		}
		
		return $this->sendJSON($result);
	}
}