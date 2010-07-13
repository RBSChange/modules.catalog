<?php
/**
 * catalog_KititemService
 * @package modules.catalog
 */
class catalog_KititemService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_KititemService
	 */
	private static $instance;

	/**
	 * @return catalog_KititemService
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
	 * @return catalog_persistentdocument_kititem
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/kititem');
	}

	/**
	 * Create a query based on 'modules_catalog/kititem' model.
	 * Return document that are instance of modules_catalog/kititem,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/kititem');
	}
	
	/**
	 * Create a query based on 'modules_catalog/kititem' model.
	 * Only documents that are strictly instance of modules_catalog/kititem
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/kititem', false);
	}
	
	/**
	 * @param catalog_persistentdocument_kit $kit
	 * @param catalog_persistentdocument_product $product
	 * @param integer $qtt
	 * @return catalog_persistentdocument_kititem
	 */
	public function createNew($kit, $product, $qtt = 1)
	{
		$item = $this->getNewDocumentInstance();
		$item->setLabel($product->getVoLabel());
		$item->setQuantity($qtt);
		$item->setProduct($product);
		return $item;
	}
	
	/**
	 * @param catalog_persistentdocument_kititem $kititem
	 * @param catalog_persistentdocument_price $kitPrice
	 * @param catalog_persistentdocument_shop $shop
	 * @param integer[] $targetIds
	 * @param Double $quantity
	 * @return boolean
	 */
	public function appendPrice($kititem, $kitPrice, $shop, $targetIds, $quantity)
	{	
		$product = 	$kititem->getDefaultProduct();
		$price = $product->getDocumentService()->getPriceByTargetIds($product, $shop, $targetIds, $quantity * $kititem->getQuantity());
		if ($price !== null)
		{
			$kitPrice->setValueWithoutTax($kitPrice->getValueWithoutTax() +  $price->getValueWithoutTax() * $kititem->getQuantity());
			$kitPrice->setValueWithTax($kitPrice->getValueWithTax() +  $price->getValueWithTax() * $kititem->getQuantity());
			if ($price->isDiscount())
			{
				$kitPrice->setOldValueWithoutTax($kitPrice->getOldValueWithoutTax() + $price->getOldValueWithoutTax() * $kititem->getQuantity());
				$kitPrice->setOldValueWithTax($kitPrice->getOldValueWithTax() + $price->getOldValueWithTax() * $kititem->getQuantity());
			}
			else
			{
				$kitPrice->setOldValueWithoutTax($kitPrice->getOldValueWithoutTax() + $price->getValueWithoutTax() * $kititem->getQuantity());
				$kitPrice->setOldValueWithTax($kitPrice->getOldValueWithTax() + $price->getValueWithTax() * $kititem->getQuantity());
			}
			
			$kitPrice->setTaxCode($price->getTaxCode());
			return true;
		}
		else
		{
			Framework::warn(var_export($kititem->getDefaultProduct(), true));
		}
		return false;
	}
	
	/**
	 * @param catalog_persistentdocument_kititem $kitItem
	 * @param array $result
	 * @return void
	 */
	public function transformToArray($kitItem, &$result)
	{
		$product = $kitItem->getProduct();	
		$statusOk = $product->isContextLangAvailable();
		$label = $statusOk ? $product->getLabel() : $product->getVoLabel();
		$showDeclination = ($product instanceof catalog_persistentdocument_declinedproduct && !$product->getSynchronizePrices());
		$result[] =  array(
				'id' => $kitItem->getId(),
				'productType' => str_replace('/', '_', $product->getDocumentModelName()),
				'productId' => $product->getId(),
				'actionrow' => $showDeclination ? '1' : '0',
				'statusOk' => $statusOk ? '': 'Non disponible',
			    'label' => $label,
			    'qtt' => $kitItem->getQuantity(),
			);	
			
		if ($showDeclination)
		{
			foreach ($product->getDeclinationArray() as $declination) 
			{
				$declinationstatusOk = $declination->isContextLangAvailable();
				$declinationlabel = $declinationstatusOk ? $declination->getLabel() : $declination->getVoLabel();
				$result[] =  array(
					'id' => 0,
					'productType' => str_replace('/', '_', $declination->getDocumentModelName()),
					'productId' => $declination->getId(),
					'statusOk' => $declinationstatusOk ? '': 'Non disponible',
					'actionrow' => '2',
				    'label' => $label,
					'sublabel' => $declinationlabel,
				    'qtt' => '');
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_kititem $document
	 * @param string $lang
	 * @param array $parameters
	 * @return string
	 */
	public function generateUrl($document, $lang, $parameters)
	{
		$kit = $document->getCurrentKit();
		if ($kit)
		{
			if (!is_array($parameters)) {$parameters = array();}
			
			if (!isset($parameters['catalogParam']))
			{
				$parameters['catalogParam'] = array('kititemid' => $document->getId());
			} 
			else
			{
				$parameters['catalogParam']['kititemid']  = $document->getId();
			}
			return LinkHelper::getDocumentUrl($kit, $lang, $parameters);
		}
		else
		{
			Framework::info(__METHOD__ . 'AAAAAAAAAAAAAAAAAAAA');
		}
		return null;
	}
}