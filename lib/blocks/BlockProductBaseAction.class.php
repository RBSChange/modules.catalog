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
		try
		{
			$quantity = max(1, intval($this->findParameterValue('quantity')));
			$cart = order_CartService::getInstance()->addProduct($product, $quantity);
			$cart->refresh();
			$this->addMessage(f_Locale::translate('&modules.catalog.frontoffice.ProductJustAdded;'));
		}
		catch (order_Exception $e)
		{
			$this->addError($e->getFrontEndUserMessage());
			if (Framework::isDebugEnabled())
			{
				Framework::debug($e->getMessage());
			}
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