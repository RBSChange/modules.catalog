<?php
/**
 * catalog_BlockProductlistAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockProductlistAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
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
			$productsToAdd = array();
			
			// List all products to add.		
			$addToCart = $request->getParameter('addToCart');
			if (is_array($addToCart))
			{
				foreach (array_keys($addToCart) as $id)
				{
					$quantity = max(intval($quantities[$id]), 1);
					$productsToAdd[$id] = array('product' => DocumentHelper::getDocumentInstance($id), 'quantity' => $quantity);
				}
			}
			else if ($request->hasParameter('selected_product'))
			{	
				$addToCart = $request->getParameter('selected_product');
				if (is_array($addToCart))
				{			
					// Add each product with no quantity but the checkbox checked (with quantity of 1).
					foreach ($addToCart as $id)
					{
						if (!array_key_exists($id, $productsToAdd))
						{
							$productsToAdd[$id] = array('product' => DocumentHelper::getDocumentInstance($id), 'quantity' => max(intval($quantities[$id]), 1));
						}
					}
				}
			}
			
			// Really add the products to the cart.
			$cs = order_CartService::getInstance();
			$cart = $cs->getDocumentInstanceFromSession();
			
			$productAdded = false;
			foreach ($productsToAdd as $item)
			{
				if ($cs->addProductToCart($cart, $item['product'], $item['quantity']))
				{
					$this->addedProductLabels[] = $item['product']->getLabel();
					$productAdded = true;
				}
				else
				{
					$this->notAddedProductLabels[] = $item['product']->getLabel();
				}
			}
			
			// Refresh the cart.
			if ($productAdded)
			{
				$cart->refresh();
				$this->addMessagesToBlock('add-to-cart');
			}
		}
		
		$request->setAttribute('shop', catalog_ShopService::getInstance()->getCurrentShop());
		
		$customer = null;
		if (catalog_ModuleService::areCustomersEnabled())
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
				$this->addedProductLabels[] = $product->getLabel();
			}
		}
		catch (Exception $e)
		{
			$this->notAddedProductLabels[] = $product->getLabel();
			if (Framework::isDebugEnabled())
			{
				Framework::debug($e->getMessage());
			}
		}
	}
}