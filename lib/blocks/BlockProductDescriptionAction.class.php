<?php
/**
 * catalog_BlockProductDescriptionAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockProductDescriptionAction extends catalog_BlockProductBaseAction
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
	
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		if (!($shop instanceof catalog_persistentdocument_shop))
		{
			Framework::warn(__METHOD__ . ' no current shop defined');
			return website_BlockView::NONE;
		}
		
		$product = $this->getDocumentParameter();
		if ($product === null || !($product instanceof catalog_persistentdocument_product) || !$product->isPublished())
		{
			Framework::warn(__METHOD__ . ' no product defined: ' . $this->getDocumentIdParameter());
			return website_BlockView::NONE;
		}
		$request->setAttribute('product', $product);
		
		return website_BlockView::SUCCESS;
	}
}