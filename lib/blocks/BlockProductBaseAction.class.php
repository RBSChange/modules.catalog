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
	protected function addProductToCart($product)
	{
		$ocs = order_CartService::getInstance();
		$cart = $ocs->getDocumentInstanceFromSession();
		$quantity = max(1, intval($this->findParameterValue('quantity')));
		if ($ocs->addProductToCart($cart, $product, $quantity))
		{
			$cart->refresh();
			$this->addMessage(f_Locale::translate('&modules.catalog.frontoffice.ProductJustAdded;'));
		}	
		else
		{
			$this->addError(f_Locale::translate('&modules.catalog.frontoffice.ProductNotAdded;'));
		}
	}
	
	/** 
	 * @param catalog_persistentdocument_product $product
	 */
	protected function addProductToFavorites($product)
	{
		if (catalog_ModuleService::getInstance()->addFavoriteProduct($product))
		{
			$this->addMessage(f_Locale::translate('&modules.catalog.frontoffice.Product-added-to-list;'));
		}
	}
}