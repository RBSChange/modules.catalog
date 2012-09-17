<?php
/**
 * catalog_BlockProductlistAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockProductlistAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function execute($request, $response)
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$request->setAttribute('shop', $shop);
				
		// Handle quanities field.
		$quantities = array();
		if ($request->hasParameter('quantities'))
		{
			foreach ($request->getParameter('quantities') as $id => $quantity)
			{
				$quantities[$id] =  intval($quantity);
			}
		}
		$request->setAttribute('quantities', $quantities);
		
		if (!$request->hasParameter('formBlockId') || $request->getParameter('formBlockId') == $this->getBlockId())
		{
			// Handle "Add to favorite".
			if ($request->hasParameter('addToFavorite'))
			{
				foreach ($request->getParameter('selected_product') as $id)
				{
					$this->addToFavorite(DocumentHelper::getDocumentInstance($id));
				}
				$this->addMessagesToBlock('add-to-favorite');
			}
			// Handle "Add to cart".
			else if ($request->hasParameter('addToCart'))
			{
				$productIdsToAdd = array();
				$addToCart = $request->getParameter('addToCart');
				if (is_array($addToCart))
				{
					$productIdsToAdd = $addToCart;
				}
				else if ($request->hasParameter('selected_product'))
				{	
					$addToCart = $request->getParameter('selected_product');
					if (is_array($addToCart))
					{		
						$productIdsToAdd = array_flip($addToCart);
					}
				}
				
				$params = array(
					'backurl' => LinkHelper::getCurrentUrl(),
					'shopId' => $shop->getId(),
					'quantities' => array_intersect_key($quantities, $productIdsToAdd),
					'productIds' => array_keys($productIdsToAdd)
				);
				
				$url = LinkHelper::getActionUrl('order', 'AddToCartMultiple', $params);
				$this->redirectToUrl($url);
			}
		}
		
		$customer = null;
		if (catalog_ModuleService::getInstance()->areCustomersEnabled())
		{
			$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
		}
		$request->setAttribute('customer', $customer);
		
		return ucfirst($request->getAttribute('blockView'));
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 */
	private function addToFavorite($product)
	{
		try
		{
			if (catalog_ModuleService::getInstance()->addFavoriteProduct($product))
			{
				$this->addedProductLabels[] = $product->getLabelAsHtml();
			}
		}
		catch (Exception $e)
		{
			$this->notAddedProductLabels[] = $product->getLabelAsHtml();
			if (Framework::isDebugEnabled())
			{
				Framework::debug($e->getMessage());
			}
		}
	}
}