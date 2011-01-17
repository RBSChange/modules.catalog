<?php
/**
 * catalog_BundleditemService
 * @package modules.catalog
 */
class catalog_BundleditemService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_BundleditemService
	 */
	private static $instance;

	/**
	 * @return catalog_BundleditemService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

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
		return $this->pp->createQuery('modules_catalog/bundleditem');
	}
	
	/**
	 * Create a query based on 'modules_catalog/bundleditem' model.
	 * Only documents that are strictly instance of modules_catalog/bundleditem
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/bundleditem', false);
	}
	
	
	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
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
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
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
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param string $lang
	 * @param array $parameters
	 * @return string
	 */
	public function generateUrl($document, $lang, $parameters)
	{
		$bundleProducts = $document->getBundleproductArrayInverse();
		if (count($bundleProducts) == 1)
		{
			if (!is_array($parameters)) {$parameters = array();}
			
			if (!isset($parameters['catalogParam']))
			{
				$parameters['catalogParam'] = array('bundleditemid' => $document->getId());
			} 
			else
			{
				$parameters['catalogParam']['bundleditemid']  = $document->getId();
			}
			return LinkHelper::getDocumentUrl($bundleProducts[0], $lang, $parameters);
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_bundleditem $bundleditem
	 * @param catalog_persistentdocument_price $itemsPrice
	 * @param catalog_persistentdocument_shop $shop
	 * @param integer[] $targetIds
	 * @param Double $quantity
	 * @return boolean
	 */
	public function appendPrice($bundleditem, $itemsPrice, $shop, $targetIds, $quantity)
	{	
		$product = 	$bundleditem->getProduct();
		$price = $product->getDocumentService()->getPriceByTargetIds($product, $shop, $targetIds, $quantity * $bundleditem->getQuantity());
		if ($price !== null)
		{
			$itemsPrice->setValueWithoutTax($itemsPrice->getValueWithoutTax() +  $price->getValueWithoutTax() * $bundleditem->getQuantity());
			$itemsPrice->setValueWithTax($itemsPrice->getValueWithTax() +  $price->getValueWithTax() * $bundleditem->getQuantity());
			if ($price->isDiscount())
			{
				$itemsPrice->setOldValueWithoutTax($itemsPrice->getOldValueWithoutTax() + $price->getOldValueWithoutTax() * $bundleditem->getQuantity());
				$itemsPrice->setOldValueWithTax($itemsPrice->getOldValueWithTax() + $price->getOldValueWithTax() * $bundleditem->getQuantity());
			}
			else
			{
				$itemsPrice->setOldValueWithoutTax($itemsPrice->getOldValueWithoutTax() + $price->getValueWithoutTax() * $bundleditem->getQuantity());
				$itemsPrice->setOldValueWithTax($itemsPrice->getOldValueWithTax() + $price->getValueWithTax() * $bundleditem->getQuantity());
			}
			
			$itemsPrice->setTaxCode($price->getTaxCode());
			return true;
		}
		else
		{
			Framework::warn(__METHOD__ . ' ' . var_export($bundleditem->getDefaultProduct(), true));
		}
		return false;
	}
}