<?php
class catalog_InitializePricesPanelAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$product = $this->getDocumentInstanceFromRequest($request);
		
		$data = array();
		$data['date'] = $request->hasParameter('date') ? $request->getParameter('date') : date_Calendar::getInstance()->toString();
		
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
		
		$data['targetTypes'] = array(
			array('value' => 'shop', 'label' => f_Locale::translateUI('&modules.catalog.bo.doceditor.panel.prices.Type-shop;')),
			array('value' => 'group', 'label' => f_Locale::translateUI('&modules.catalog.bo.doceditor.panel.prices.Type-group;')),
			array('value' => 'customer', 'label' => f_Locale::translateUI('&modules.catalog.bo.doceditor.panel.prices.Type-customer;')),
			array('value' => 'all', 'label' => f_Locale::translateUI('&modules.catalog.bo.doceditor.panel.prices.Type-all;'))
		);
		
		$data['targetType'] = $request->hasParameter('targetType') ? $request->getParameter('targetType') : 'shop';
		$data['targetId'] = $request->hasParameter('targetId') ? $request->getParameter('targetId') : 0;
		
		return $this->sendJSON($data);
	}
}