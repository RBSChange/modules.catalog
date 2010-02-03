<?php
class catalog_LoadPriceTargetListAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$ps = catalog_PriceService::getInstance();
		$targetType = $request->getParameter('targetType');
		
		$data = array();
		if ($ps->canChooseTarget($targetType))
		{
			$docs = $ps->getAvailableTargets(
				$targetType,
				$request->getParameter('shopId')
			);
			$data['canChoose'] = true;
			$data['nodes'] = array();
			foreach ($docs as $doc)
			{
				$data['nodes'][] = array(
					'label' => $doc->getLabel(),
					'id' => $doc->getId()
				);
			}
		}
		else
		{
			$data['canChoose'] = false;
		}
		return $this->sendJSON($data);
	}
}