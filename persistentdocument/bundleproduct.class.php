<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_bundleproduct
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_bundleproduct extends catalog_persistentdocument_bundleproductbase 
	implements catalog_StockableDocument
{
	/**
	 * @see catalog_persistentdocument_product::getDetailBlockName()
	 *
	 * @return String
	 */
	public function getDetailBlockName()
	{
		return 'bundleproduct';
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return media_persistentdocument_media[]
	 */
	public function getAllVisuals($shop)
	{
		$result = parent::getAllVisuals($shop);
		foreach ($this->getBundleditemArray() as $bundleditem)
		{
			$media = $bundleditem->getProduct()->getVisual();
			if ($media !== null)
			{
				$result[] = $media;
			}
		}
		return array_unique($result);
	}

	/**
	 * @var integer
	 */
	private $newBundledItemQtt = 1;
	
	/**
	 * @var string for example "4526,785445,2355" 
	 */
	private $newBundledItemProducts = null;
	
	/**
	 * @return integer
	 */
	public function getNewBundledItemQtt()
	{
		return $this->newBundledItemQtt;
	}
	
	/**
	 * @param integer $newBundledItemQtt
	 */
	public function setNewBundledItemQtt($newBundledItemQtt)
	{
		$this->newBundledItemQtt = $newBundledItemQtt;
	}
	
	/**
	 * @return string
	 */
	public function getNewBundledItemProducts()
	{
		return $this->newBundledItemProducts;
	}
	
	/**
	 * @param string $newBundledItemProducts
	 */
	public function setNewBundledItemProducts($newBundledItemProducts)
	{
		Framework::info(__METHOD__ . "($newBundledItemProducts)");
		$this->newBundledItemProducts = $newBundledItemProducts;
		$this->setModificationdate(null);
	}

	/**
	 * @return catalog_persistentdocument_product[]
	 */
	public function getNewBundledItemProductsDocument()
	{
		$result = array();
		if (f_util_StringUtils::isNotEmpty($this->newBundledItemProducts))
		{
			$ids = explode(',', $this->newBundledItemProducts);
			foreach ($ids as $id) 
			{
				Framework::info(__METHOD__ . "($id)");
				$result[] = DocumentHelper::getDocumentInstance($id, 'modules_catalog/product');
			}
		}
		return $result;
	}
	
	public function resetNewBundledItemProducts()
	{
		$this->newBundledItemProducts = null;
		$this->newBundledItemQtt = 1;
	}
	
	/**
	 * @return string JSON
	 */
	public function getBundleditemsJSON()
	{
		$result = array();
		foreach ($this->getBundleditemArray() as $bundledItem) 
		{
			$product = $bundledItem->getProduct();	
			$satusOk = $product->isContextLangAvailable();
			$label = $satusOk ? $product->getLabel() : $product->getVoLabel();
			
			$result[] = array(
				'id' => $bundledItem->getId(),
				'productType' => str_replace('/', '_', $product->getDocumentModelName()),
				'productId' => $product->getId(),
				'satusOk' => $satusOk ? '': 'Non disponible',
			    'label' => $label,
			    'qtt' => $bundledItem->getQuantity(),
			);
		}
		return JsonService::getInstance()->encode($result);
	}
	
	/**
	 * @var catalog_persistentdocument_bundleditem[]
	 */
	private $bundledItemsToDelete;
	
	/**
	 * @param string $json
	 */
	public function setBundleditemsJSON($json)
	{
		if (!is_array($this->bundledItemsToDelete))
		{
			$this->bundledItemsToDelete = array();
		}
		
		foreach ($this->getBundleditemArray() as $bundledItem)
		{
			$this->bundledItemsToDelete[$bundledItem->getId()] = $bundledItem;
		}
		
		$this->removeAllBundleditem();
		$result = JsonService::getInstance()->decode($json);
		foreach ($result as $datas) 
		{
			$qtt = intval($datas['qtt']);	
			if ($qtt > 0)
			{
				$id = intval($datas['id']);
				Framework::info(__METHOD__ . " $id, $qtt");
				$bundledItem = $this->bundledItemsToDelete[$id];
				$bundledItem->setQuantity($qtt);
				$this->addBundleditem($bundledItem);
				unset($this->bundledItemsToDelete[$id]);
			}
		}
		
		$this->setModificationdate(null);
	}
	
	/**
	 * @return catalog_persistentdocument_bundleditem[]
	 */
	public function getBundledItemsToDelete()
	{
		if (!is_array($this->bundledItemsToDelete))
		{
			return array();
		}
		$result = $this->bundledItemsToDelete;
		$this->bundledItemsToDelete = null;
		return $result;
	}
	
	/**
	 * @return string
	 */
	public function getOrderLabel()
	{
		$label = array();
		$label[] = $this->getLabel() . ':';
		foreach ($this->getBundleditemArray() as $bundleditem) 
		{
			$label[] = "\t" . $bundleditem->getQuantity() . " x " . $bundleditem->getProduct()->getLabel();
		}
		return implode("\n", $label);
	}
	
	/**
	 * @return string HTMLFragment
	 */
	public function getOrderLabelAsHtml()
	{
		$html = array();
		
		$html[] = LinkHelper::getLink($this) . '<br />';
		$html[] = '<ol>';
		foreach ($this->getBundleditemArray() as $bundleditem) 
		{
			$html[] = '<li>';
			$url = LinkHelper::getDocumentUrl($bundleditem);
			if ($url)
			{
				$html[] = '<a class="link" href="'.$url.'">' . $bundleditem->getTitleAsHtml() . '</a>';
			}
			else
			{
				$html[] = $bundleditem->getTitleAsHtml();
			}
			$html[] = '</li>';
		}
		$html[] = '</ol>';
		return implode('', $html);
	}

	/**
	 * @var catalog_persistentdocument_price
	 */
	private $itemsPrices = array();
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @param customer_persistentdocument_customer $customer nullable
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getItemsPrice($shop, $billingArea, $customer, $quantity = 1)
	{
		$key = $shop->getId() . '.' . ($customer ? $customer->getId() : '0') . '.' . $quantity;
		if (!isset($this->itemsPrices[$key]))
		{
			$targetIds = catalog_PriceService::getInstance()->convertCustomerToTargetIds($customer);
			$this->itemsPrices[$key] = $this->getDocumentService()->getItemsPriceByTargetIds($this, $shop, $billingArea, $targetIds, $quantity);
		}
		return $this->itemsPrices[$key];
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @param customer_persistentdocument_customer $customer nullable
	 * @param float $quantity
	 * @return catalog_persistentdocument_price|null
	 */
	public function getPriceDifference($shop, $billingArea, $customer, $quantity = 1)
	{
		$price1 = $this->getItemsPrice($shop, $billingArea, $customer, $quantity);
		// Unlike a kit, a bundle may include a product without price and be displayed in the shop. 
		// In this case, price difference can't be calculated.
		if ($price1 === null)
		{
			return null;
		}
		$price2 = $this->getPrice($shop, $billingArea, $customer, $quantity);
		return catalog_PriceService::getInstance()->getPriceDifference($price1, $price2);
	}

	/**
	 * catalog_BundledProduct[]
	 */
	public function getBundledProducts()
	{
		$bundledProducts = array();
		foreach ($this->getBundleditemArray() as $bundledItem)
		{
			$bundledProducts[] = new catalog_BundledProductImpl($bundledItem->getProduct(), $bundledItem->getQuantity());
		}
		return $bundledProducts;
	}
}
