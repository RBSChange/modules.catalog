<?php
/**
 * catalog_BlockConfigurateKitAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockConfigurateKitAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackofficeEdition())
		{
			return website_BlockView::NONE;
		}
		
		$gr = change_Controller::getInstance()->getContext()->getRequest();
		if ($gr->hasParameter('cartLineIndex'))
		{
			$cart = order_CartService::getInstance()->getDocumentInstanceFromSession();
			$cartLine = $cart->getCartLine($gr->getParameter('cartLineIndex'));
			$product = $cartLine->getProduct();
			$request->setAttribute('updateLine', true);
			$request->setAttribute('cartLineIndex', $gr->getParameter('cartLineIndex'));
		}
		else
		{
			$request->setAttribute('updateLine', false);
			$product = DocumentHelper::getDocumentInstanceIfExists($gr->getParameter('productId'));
		}
		
		if (!($product instanceof catalog_persistentdocument_kit) || !$product->isPublished())
		{
			return website_BlockView::NONE;
		}
		
		// Handle declination selection.
		$customItems = array();
		if ($request->hasParameter('declinationIds'))
		{
			$declinationInfos = $request->getParameter('declinationIds');
			if (is_array($declinationInfos))
			{
				foreach ($product->getKititemArray() as $kititem)
				{
					$kititemId = $kititem->getId();
					if ($kititem->getDeclinable() && isset($declinationInfos[$kititemId]))
					{
						$customProduct = catalog_persistentdocument_product::getInstanceById($declinationInfos[$kititemId]);
						foreach ($kititem->getProduct()->getDeclinations() as $declination)
						{
							if ($customProduct === $declination)
							{
								$kititem->setCurrentProduct($declination);
								$customItems[$kititem->getCurrentKey()] = $declination->getId();
							}
						}
					}
				}
			}
		}
		else 
		{
			foreach ($product->getKititemArray() as $kititem)
			{
				/* @var $kititem catalog_persistentdocument_kititem */
				if ($kititem->getDeclinable())
				{
					$currentProduct = $kititem->getCurrentProduct();
					if ($currentProduct)
					{
						$customItems[$kititem->getCurrentKey()] = $currentProduct->getId();
					}
				}
			}
		}
		
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		if ($gr->hasParameter('quantity'))
		{
			$quantity = intval($gr->getParameter('quantity'));
		}
		elseif ($cartLine)
		{
			$quantity = $cartLine->getQuantity();
		}
		else
		{
			$quantity = 1;
		}
		$backUrl = $gr->getParameter('backurl');
		
		// If add to cart, redirect to order/AddToCart action.
		if ($gr->hasParameter('addConfiguredKitToCart'))
		{
			$params = array(
				'backurl' => $backUrl,
				'shopId' => $shop->getId(),
				'quantity' => $quantity,
				'productId' => $product->getId(),
				'customitems' => $customItems
			);
			return $this->redirectToUrl(LinkHelper::getActionUrl('order', 'AddToCart', $params));
		}
		elseif ($gr->hasParameter('updateKitConfigurationInCart') && $gr->hasParameter('cartLineIndex'))
		{
			$properties = array();
			$product->getDocumentService()->getProductToAddToCart($product, $shop, $quantity, $properties);
			$cartLine->mergePropertiesArray($properties);
			$cartLine->setQuantity($quantity);
			$cart->refresh();
			$cart->addSuccessMessage(LocaleService::getInstance()->trans('m.catalog.fo.product-configuration-updated', array('ucf')));
			$cart->save();
			return $this->redirectToUrl(order_CartService::getInstance()->getCartUrl($cart));
		}
		
		$request->setAttribute('backUrl', $backUrl);
		$request->setAttribute('quantity', $quantity);
		$request->setAttribute('shop', $shop);
		$request->setAttribute('kit', $product);
		
		$spwt = $this->getShowPricesWithTax($shop);
		$spwot = $this->getShowPricesWithoutTax($shop);
		$request->setAttribute('showPrices', $spwt || $spwot);
		$request->setAttribute('showPricesWithTax', $spwt);
		$request->setAttribute('showPricesWithoutTax', $spwot);
		if ($spwt || $spwot)
		{
			$customer = null;
			if (catalog_ModuleService::areCustomersEnabled())
			{
				$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
			}
			
			$billingArea = $shop->getCurrentBillingArea();
			$price = $product->getPrice($shop, $billingArea, $customer);
			if ($price)
			{
				$request->setAttribute('defaultPrice', $price);
				$request->setAttribute('differencePrice', $product->getPriceDifference($shop, $billingArea, $customer));
			}
		}
		
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return boolean
	 */
	protected function getShowPricesWithTax($shop)
	{
		switch ($this->getConfiguration()->getShowPricesWithTax())
		{
			case 'asInShop':
				return $shop->getDisplayPriceWithTax();
			case 'true':
				return true;
			case 'false':
				return false;
		}
		return false;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return boolean
	 */
	protected function getShowPricesWithoutTax($shop)
	{
		switch ($this->getConfiguration()->getShowPricesWithoutTax())
		{
			case 'asInShop':
				return $shop->getDisplayPriceWithoutTax();
			case 'true':
				return true;
			case 'false':
				return false;
		}
		return false;
	}
}