<?php
/**
 * catalog_BlockProductCrossSellingAction
 * @package modules.catalog
 */
class catalog_BlockProductCrossSellingAction extends website_BlockAction
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
    	if ($product instanceof catalog_persistentdocument_productdeclination)
    	{
    		$product = $product->getRelatedDeclinedProduct();
    	}
    	
	    if ($product === null || !($product instanceof catalog_persistentdocument_product) || !$product->isPublished())
	    {
	    	if ($product !== null)
	    	{
	    		Framework::error(__METHOD__ . ' Invalid product type');
	    	}
	    	return website_BlockView::NONE;
	    }
	    $request->setAttribute('product', $product);
    	$configuration = $this->getConfiguration();
    	$relatedProducts = $product->getDisplayableCrossSelling($shop, $configuration->getType(), $configuration->getSortby());
    	
    	if (count($relatedProducts) > 0)
		{
			$list = list_ListService::getInstance()->getByListId('modules_catalog/crosssellingtypes');
			$request->setAttribute('title', $list->getItemByValue($configuration->getType())->getLabel());
			$request->setAttribute('relatedProducts', array_slice($relatedProducts, 0, $configuration->getMaxdisplayed()));
			$request->setAttribute('shop', $shop);
			
			$customer = null;
			if (catalog_ModuleService::areCustomersEnabled())
			{
				$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
			}
			$request->setAttribute('customer', $customer);
			
			if ($configuration->getAddlinktoall())
			{
				try
				{
					$page = TagService::getInstance()->getDocumentBySiblingTag('functional_catalog_crosssellinglist-page', $this->getPage()->getPersistentPage());
					$request->setAttribute('hrefToAll', LinkHelper::getDocumentUrl($page, null, array('catalogParam[cmpref]' => $product->getId(), 'catalogParam[relationType]' => $configuration->getType())));
				}
				catch (Exception $e)
				{
					if (Framework::isDebugEnabled())
					{
						Framework::exception($e);
					}
				}
				
			}
			return website_BlockView::SUCCESS;
		}
    	else 
    	{
    		return website_BlockView::NONE;
    	}
	}
}