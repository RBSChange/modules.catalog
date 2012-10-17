<?php
/**
 * @package modules.catalog
 * @method catalog_BundleditemService getInstance()
 */
class catalog_BundleditemService extends f_persistentdocument_DocumentService
{
	/**
	 * @return catalog_persistentdocument_bundleditem
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/bundleditem');
	}

	/**
	 * Create a query based on 'modules_catalog/bundleditem' model.
	 * Return document that are instance of modules_catalog/bundleditem,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/bundleditem');
	}
	
	/**
	 * Create a query based on 'modules_catalog/bundleditem' model.
	 * Only documents that are strictly instance of modules_catalog/bundleditem
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/bundleditem', false);
	}
	
	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		$product = $document->getProduct();
		if ($product == null)
		{
			$oldProduct = DocumentHelper::getDocumentInstanceIfExists($document->getProductOldValueId());
			$kit = array_pop($document->getBundleproductArrayInverse());
			throw new Exception(LocaleService::getInstance()->transBO('m.catalog.errors.bundleitem.cannot-delete-associated-product', array('ucf'), array(
				'product' => $oldProduct->getLabel(), 'bundlename' => $kit->getLabel())));
		}
	}
	
	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId)
	{
		if (f_util_StringUtils::isEmpty($document->getLabel()))
		{
			$document->setLabel($document->getProduct()->getVoLabel());	
		}
	}
	
	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		if ($parentNodeId)
		{
			$pdoc = DocumentHelper::getDocumentInstance($parentNodeId);
			if ($pdoc instanceof catalog_persistentdocument_bundleproduct)
			{
				if ($pdoc->getIndexofBundleditem($document) === -1)
				{
					$pdoc->addBundleditem($document);
					$pdoc->save();
				}
			}
		}
	}
	
	/**
	 * @param website_UrlRewritingService $urlRewritingService
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param website_persistentdocument_website $website
	 * @param string $lang
	 * @param array $parameters
	 * @return f_web_Link | null
	 */
	public function getWebLink($urlRewritingService, $document, $website, $lang, $parameters)
	{
		$bundleProducts = $document->getBundleproductArrayInverse();
		if (count($bundleProducts) == 1)
		{
			if (!isset($parameters['catalogParam']))
			{
				$parameters['catalogParam'] = array('bundleditemid' => $document->getId());
			} 
			else
			{
				$parameters['catalogParam']['bundleditemid']  = $document->getId();
			}
			return $urlRewritingService->getDocumentLinkForWebsite($bundleProducts[0], $website, $lang, $parameters);
		}
		return null;
		
	}

	/**
	 * @param catalog_persistentdocument_bundleditem $bundleditem
	 * @param catalog_persistentdocument_price $itemsPrice
	 * @param catalog_persistentdocument_shop $shop
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @param integer[] $targetIds
	 * @param float $quantity
	 * @return boolean
	 */
	public function appendPrice($bundleditem, $itemsPrice, $shop, $billingArea, $targetIds, $quantity)
	{	
		$product = 	$bundleditem->getProduct();
		$price = $product->getDocumentService()->getPriceByTargetIds($product, $shop, $billingArea, $targetIds, $quantity * $bundleditem->getQuantity());
		if ($price !== null)
		{
			$itemsPrice->setValueWithoutTax($itemsPrice->getValueWithoutTax() +  $price->getValueWithoutTax() * $bundleditem->getQuantity());
			if ($price->isDiscount())
			{
				$itemsPrice->setOldValueWithoutTax($itemsPrice->getOldValueWithoutTax() + $price->getOldValueWithoutTax() * $bundleditem->getQuantity());
			}
			else
			{
				$itemsPrice->setOldValueWithoutTax($itemsPrice->getOldValueWithoutTax() + $price->getValueWithoutTax() * $bundleditem->getQuantity());
			}
			
			$itemsPrice->setTaxCategory($price->getTaxCategory());
			return true;
		}
		else
		{
			// Unlike a kit, a bundle may include a product without price and be displayed in the shop.
			Framework::info(__METHOD__ . ' No price on product ' . $bundleditem->getProduct()->getId());
		}
		return false;
	}
}