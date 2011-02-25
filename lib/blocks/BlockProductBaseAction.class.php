<?php
/**
 * catalog_BlockProductBaseAction
 * @package modules.catalog
 */
abstract class catalog_BlockProductBaseAction extends website_BlockAction
{
	/** 
	 * @param catalog_persistentdocument_product $product
	 */
	protected function addProductToFavorites($product)
	{
		if (catalog_ModuleService::getInstance()->addFavoriteProduct($product))
		{
			$this->addMessage(LocaleService::getInstance()->transFO('m.catalog.frontoffice.product-added-to-list', array('ucf')));
		}
	}
	
	// Deprecated.
	
	/** 
	 * @deprecated (will be removed in 4.0) use order_AddToCartAction
	 */
	protected function addProductToCart($product)
	{
		$ocs = order_CartService::getInstance();
		$cart = $ocs->getDocumentInstanceFromSession();
		$quantity = max(1, intval($this->findParameterValue('quantity')));
		if ($ocs->addProductToCart($cart, $product, $quantity))
		{
			$cart->refresh();
			$this->addMessage(LocaleService::getInstance()->transFO('m.catalog.frontoffice.productjustadded', array('ucf')));
		}	
		else
		{
			foreach ($cart->getTransientErrorMessages() as $msg)
			{
				$this->addError($msg);
			}
			$cart->clearTransientErrorMessages();
		}
	}
}