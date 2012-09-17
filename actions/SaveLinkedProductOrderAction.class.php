<?php
/**
 * catalog_SaveLinkedProductOrderAction
 * @package modules.catalog.actions
 */
class catalog_SaveLinkedProductOrderAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		if (!$request->hasParameter('co'))
		{
			return $this->sendJSONError(LocaleService::getInstance()->trans('m.generic.backoffice.orderchildreninvalidparameterserrormessage', array('ucf')));
		}
		$itemOrder = array_flip($request->getParameter('co'));
		
		$target = DocumentHelper::getDocumentInstance($request->getParameter('targetId'));
		$linkType = $request->getParameter('linkType');
		$applyToAllDeclinations = $request->hasParameter('applyToAllDeclinations') && $request->getParameter('applyToAllDeclinations') == 'true';

		$cps = catalog_CompiledcrossitemService::getInstance();
		$cps->setPositionsByTargetAndLinkType($target, $linkType, $itemOrder, $applyToAllDeclinations);
		
		$result['nodes'] = $cps->getSortingInfosByTargetAndLinkType($target, $linkType);

		return $this->sendJSON($result);
	}
}