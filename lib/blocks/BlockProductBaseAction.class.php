<?php
/**
 * catalog_BlockProductBaseAction
 * @package modules.catalog
 */
abstract class catalog_BlockProductBaseAction extends website_BlockAction
{
	public function initialize($request)
	{
		$request->setAttribute("formBlockId", $this->getBlockId());
	}
	
	public function isCacheEnabled()
	{
		// Disable cache if old addToXXX parameters are used
		$request = $this->getRequest();
		return parent::isCacheEnabled() && !$request->hasParameter("addToCart")
			&& !$request->hasParameter("addToList");
	}
	
	// Deprecated.
	/**
	 * @deprecated (will be removed in 4.0) use catalog_AddToListAction
	 */
	protected function addProductToFavorites($product)
	{
		if (catalog_ModuleService::getInstance()->addFavoriteProduct($product))
		{
			$this->addMessage(LocaleService::getInstance()->transFO('m.catalog.frontoffice.product-added-to-list', array('ucf')));
		}
	}
	
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
	
	/**
	 * @deprecated (will be removed in 4.0) use order_AddToCartAction
	 */
	protected function addProductToCartForCurrentBlock($product)
	{
		$request = website_BlockController::getInstance()->getRequest();
		if ($request->hasParameter('formBlockId') && $request->getParameter('formBlockId') != $this->getBlockId())
		{
			return;
		}
		$this->addProductToCart($product);
	}
}