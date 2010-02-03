<?php
/**
 * catalog_BlockProductCrossSellingListAction
 * @package modules.catalog
 */
class catalog_BlockProductCrossSellingListAction extends catalog_BlockProductlistBaseAction
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
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}

		$shop = catalog_ShopService::getInstance()->getCurrentShop();
    	$product = $this->getDocumentParameter();
    	$relatedProducts = $product->getDisplayableCrossSelling($shop, $request->getParameter('relationType'));
    	
		// Prepare display configuration.
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$displayConfig = $this->getDisplayConfig($shop);
		$request->setAttribute('displayConfig', $displayConfig);
		 
		$list = list_ListService::getInstance()->getByListId('modules_catalog/crosssellingtypes');
		$productUrl = LinkHelper::getDocumentUrl($product);
		$replacements = array('productLink' => '<a class="link" href="'.$productUrl.'">'.$product->getLabel().'</a>');
		$request->setAttribute('blockTitle', f_Locale::translate('&modules.catalog.frontoffice.Cross-selling-'.$request->getParameter('relationType').';', $replacements));
		$request->setAttribute('shop', $shop);
		$request->setAttribute('products', $relatedProducts);
		$request->setAttribute('blockView', 'table');
		return $this->forward('catalog', 'productlist');
	}
}