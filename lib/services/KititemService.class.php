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
		$item->setQuantity($qtt);
		$item->setProduct($product);
		return $item;
	}
	
	/**
	 * @param catalog_persistentdocument_kititem $document
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
	 * @param catalog_persistentdocument_kititem $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		if ($parentNodeId)
		{
			$pdoc = DocumentHelper::getDocumentInstance($parentNodeId);
			if ($pdoc instanceof catalog_persistentdocument_kit)
			{
				if ($pdoc->getIndexofKititem($document) === -1)
				{
					$pdoc->addKititem($document);
					$pdoc->save();
				}
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_kititem $kititem
	 * @param catalog_persistentdocument_shop $shop
	 * @param integer[] $targetIds
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getKititemPrice($kititem, $shop, $targetIds, $quantity)
	{
		$product = $kititem->getDefaultProduct();
		return $product->getDocumentService()->getPriceByTargetIds($product, $shop, $targetIds, $quantity * $kititem->getQuantity());
		
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
		$price = $this->getKititemPrice($kititem, $shop, $targetIds, $quantity);
		if ($price instanceof catalog_persistentdocument_price)
		{
			$kitPrice->setValueWithoutTax($kitPrice->getValueWithoutTax() +  $price->getValueWithoutTax() * $kititem->getQuantity());
			if ($price->isDiscount())
			{
				$kitPrice->setOldValueWithoutTax($kitPrice->getOldValueWithoutTax() + $price->getOldValueWithoutTax() * $kititem->getQuantity());
			}
			else
			{
				$kitPrice->setOldValueWithoutTax($kitPrice->getOldValueWithoutTax() + $price->getValueWithoutTax() * $kititem->getQuantity());
			}
			$kitPrice->setCurrencyId($price->getCurrencyId());
			$kitPrice->setTaxCategory($price->getTaxCategory());
			return true;
		}
		else
		{
			Framework::warn(__METHOD__ . ' ' . var_export($kititem->getDefaultProduct(), true));
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
		$showDeclination = false;
		$master = $product;
		if ($product instanceof catalog_DeclinableProduct)
		{
			if ($kitItem->getDeclinable())
			{
				$master = $product->getDeclinedproduct();
				if (!$master->getSynchronizePrices())
				{
					$showDeclination = true;
				}
			}
		}
		$statusOk = $master->isContextLangAvailable();
		$label = $statusOk ? $master->getLabel() : $master->getVoLabel();
		
		
		$result[] =  array(
				'id' => $kitItem->getId(),
				'productType' => str_replace('/', '_', $master->getDocumentModelName()),
				'productId' => $master->getId(),
				'actionrow' => $showDeclination ? '1' : '0',
				'statusOk' => $statusOk ? '': 'Non disponible',
			    'label' => $label,
			    'qtt' => $kitItem->getQuantity(),
		);	
			
		if ($showDeclination)
		{
			foreach ($product->getDeclinations() as $declination) 
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
				    'qtt' => '',
					'declinable' => false,
				);
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
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' No current kit.');
			}
		}
		return null;
	}
}