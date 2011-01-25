<?php
class catalog_InitializePricesPanelAction extends f_action_BaseJSONAction
{
	
	/**
	 * @param Request $request
	 * @return catalog_persistentdocument_product
	 */
	private function getProductFromRequest($request)
	{
		return $this->getDocumentInstanceFromRequest($request);
	}
	
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$product = $this->getProductFromRequest($request);
		$data = array('productId' => $product->getId());
		
		if (!$product->isPricePanelEnabled())
		{
			$data['enabled'] = false;
			$data['message'] = $product->getPricePanelDisabledMessage();
		}		
		else
		{
			$data['enabled'] = true;
			$date = $request->getParameter('date');
			if (f_util_StringUtils::isEmpty($date)) {
				$date = null;
			}
			$data['date'] = $date;
			
			$data['shops'] = array();
			foreach (catalog_ShopService::getInstance()->createQuery()->find() as $shop)
			{ 
				$data['shops'][] = array(
					'label' => $shop->getLabel(),
					'id' => $shop->getId(),
					'published' => $shop->isPublished(),
					'contains' => $product->isInShop($shop->getId())
				);
			}
			
			if ($request->hasParameter('shop'))
			{
				$data['shop'] = $request->getParameter('shop');
			}
			else if (isset($data['shops'][0]))
			{
				$data['shop'] = $data['shops'][0]['id'];
			}
			
			$data['targetTypes'] = array();
			$targetType = $request->hasParameter('targetType') ? $request->getParameter('targetType') : 'all';
			$cps = catalog_PriceService::getInstance();
			foreach ($cps->getAvailableTargetTypes($product) as $type => $infos) 
			{
				if ($targetType == $type) {$data['targetType'] = $targetType;}
				$data['targetTypes'][] = array('value' => $type, 'label' => $infos['label']);
			}
			if (!array_key_exists('targetType', $data)){$data['targetType'] = 'all';}
			
			$targetId = $request->getParameter('targetId');
			if (f_util_StringUtils::isEmpty($targetId))
			{
				$targetId = null;
			}
			$targetInfo = $cps->getTargetInfo($data['targetType'], $data['shop']);
			if ($targetInfo !== null)
			{
				$data['targetInfo'] = $targetInfo;
				if ($targetInfo["type"] == "dropdown" && isset($targetInfo["options"]) && count($targetInfo["options"]) > 0)
				{
					$targetId = $targetInfo["options"][0]["id"];
					$data['targetIdSelect'] = $targetId;
				}
			}
			
			if ($targetId !== null)
			{
				$data['targetId'] = $targetId;
			}
			
			// Add price list.		
			$prices = $cps->getPricesForDate($data['date'], $data['productId'], $data['shop'], $targetId);		
			$pricelist = array();
			foreach ($prices as $price)
			{
				$cps->transformToArray($price, $pricelist);
			}
			$data['pricelist'] = JsonService::getInstance()->encode($pricelist);
		}
		return $this->sendJSON($data);
	}
}