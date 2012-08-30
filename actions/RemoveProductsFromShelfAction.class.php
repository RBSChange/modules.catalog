<?php
/**
 * catalog_RemoveProductsFromShelfAction
 * @package modules.catalog.actions
 */
class catalog_RemoveProductsFromShelfAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();
		$shelf = catalog_persistentdocument_shelf::getInstanceById($this->getDocumentIdFromRequest($request));
		$productIds = $request->getParameter('products');
		if (is_array($productIds))
		{
			$products = array();
			$declinedProducts = array();
			$tm = f_persistentdocument_TransactionManager::getInstance();
			try
			{
				$tm->beginTransaction();
				foreach ($productIds as $productId)
				{
					$product = DocumentHelper::getDocumentInstanceIfExists($productId);
					if ($product instanceof catalog_persistentdocument_product)
					{
						$products[] = $product;
						$result['products'][] = $product->getId();
					}
					elseif  ($product instanceof catalog_persistentdocument_declinedproduct)
					{
						$declinedProducts[] = $product;
						$result['products'][] = $product->getId();
					}
				}
				if (count($products))
				{
					$shelf->getDocumentService()->removeProducts($shelf, $products);
				}
				if (count($declinedProducts))
				{
					$shelf->getDocumentService()->removeDeclinedProducts($shelf, $declinedProducts);
				}
				
				$tm->commit();
			} 
			catch (Exception $e) 
			{
				$tm->rollback($e);
				throw $e;
			}
		}
		
		// Write your code here to set content in $result.

		return $this->sendJSON($result);
	}
	
	/**
	 * @return false
	 */
	protected function isDocumentAction()
	{
		return false;
	}	
}