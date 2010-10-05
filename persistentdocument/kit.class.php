<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_kit
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_kit extends catalog_persistentdocument_kitbase implements catalog_StockableDocument
{
	/**
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
//	protected function addTreeAttributes($moduleName, $treeType, &$nodeAttributes)
//	{
//	}
	
	/**
	 * @param string $actionType
	 * @param array $formProperties
	 */
//	public function addFormProperties($propertiesNames, &$formProperties)
//	{	
//	}

	/**
	 * @see catalog_persistentdocument_product::getDetailBlockName()
	 *
	 * @return String
	 */
	public function getDetailBlockName()
	{
		return 'kitproduct';
	}
	
	//BO Functions
	
	/**
	 * @var integer
	 */
	private $newKitItemQtt = 1;
	/**
	 * @example "4526,785445,2355"
	 * @var string
	 */
	private $newKitItemProductIds = null;

	/**
	 * @var catalog_persistentdocument_kititem[]
	 */
	private $kitItemsToDelete;

	
	/**
	 * @return integer
	 */
	public function getNewKitItemQtt()
	{
		return $this->newKitItemQtt;
	}
	
	/**
	 * @param integer $newKitItemQtt
	 */
	public function setNewKitItemQtt($newKitItemQtt)
	{
		$this->newKitItemQtt = $newKitItemQtt;
	}

	/**
	 * @return string
	 */
	public function getNewKitItemProductIds()
	{
		return $this->newKitItemProductIds;
	}
	
	/**
	 * @param string $newKitItemProducts
	 */
	public function setNewKitItemProductIds($newKitItemProductIds)
	{
		$this->newKitItemProductIds = $newKitItemProductIds;
		$this->setModificationdate(null);
	}
	
	/**
	 * @return catalog_persistentdocument_product[]
	 */
	public function getNewKitItemProductsDocument()
	{
		$result = array();
		if (f_util_StringUtils::isNotEmpty($this->newKitItemProductIds))
		{
			$ids = explode(',', $this->newKitItemProductIds);
			foreach ($ids as $id) 
			{
				$result[] = DocumentHelper::getDocumentInstance($id, 'modules_catalog/product');
			}
		}
		return $result;
	}
	
	/**
	 * @return string JSON
	 */
	public function getKititemsJSON()
	{
		$result = array();
		foreach ($this->getKititemArray() as $kitItem) 
		{
			catalog_KititemService::getInstance()->transformToArray($kitItem, $result);
		}
		return JsonService::getInstance()->encode($result);
	}
	
	/**
	 * @param string $json
	 */
	public function setKititemsJSON($json)
	{
		if (!is_array($this->kitItemsToDelete))
		{
			$this->kitItemsToDelete = array();
		}
		
		foreach ($this->getKititemArray() as $kitItem)
		{
			$this->kitItemsToDelete[$kitItem->getId()] = $kitItem;
		}
		
		$this->removeAllKititem();
		$result = JsonService::getInstance()->decode($json);
		foreach ($result as $datas) 
		{
			$qtt = intval($datas['qtt']);	
			if ($qtt > 0)
			{
				$id = intval($datas['id']);
				$kitItem = $this->kitItemsToDelete[$id];
				$kitItem->setQuantity($qtt);
				$this->addKititem($kitItem);
				unset($this->kitItemsToDelete[$id]);
			}
		}
		
		$this->setModificationdate(null);
	}	
	
	/**
	 * @return catalog_persistentdocument_kititem[]
	 */
	public function getKitItemsToDelete()
	{
		if (!is_array($this->kitItemsToDelete))
		{
			return array();
		}
		$result = $this->kitItemsToDelete;
		$this->kitItemsToDelete = null;
		return $result;
	}
	
	/**
	 * @see catalog_StockableDocument::addStockQuantity()
	 *
	 * @param Double $quantity
	 * @return Double
	 */
	function addStockQuantity($quantity)
	{
		$stock = null;
		if ($this->getKititemCount())
		{
			foreach ($this->getKititemArray() as $kitItem) 
			{
				$product = $kitItem->getProduct();
				if ($product instanceof catalog_StockableDocument)
				{
					$newStock = $product->addStockQuantity($quantity * $kitItem->getQuantity());
					$product->save();
					
					if ($newStock !== null && ($stock === null || $newStock < $stock))
					{
						$stock = $newStock;
					}
				}
			}
		}
		return $stock;
	}
	
	/**
	 * @see catalog_StockableDocument::getStockLevel()
	 *
	 * @return String
	 */
	function getStockLevel()
	{
		if ($this->getKititemCount())
		{
			foreach ($this->getKititemArray() as $kitItem) 
			{
				$product = $kitItem->getDefaultProduct();
				if ($product instanceof catalog_StockableDocument)
				{
					if ($product->getStockLevel() === catalog_StockService::LEVEL_UNAVAILABLE)
					{
						return catalog_StockService::LEVEL_UNAVAILABLE;
					}
				}
			}
			return catalog_StockService::LEVEL_AVAILABLE;
		}	
		return catalog_StockService::LEVEL_UNAVAILABLE;
	}
	
	/**
	 * @see catalog_StockableDocument::getStockQuantity()
	 *
	 * @return Double
	 */
	function getStockQuantity()
	{
		$quantity = null;
		if ($this->getKititemCount())
		{
			foreach ($this->getKititemArray() as $kitItem) 
			{
				$product = $kitItem->getDefaultProduct();
				if ($product instanceof catalog_StockableDocument)
				{
					$newStock = $product->getStockQuantity();
					if ($newStock !== null && $kitItem->getQuantity() > 0)
					{
						$realStock = $newStock / $kitItem->getQuantity();
						if ($quantity === null || $quantity > $realStock)
						{
							$quantity = $realStock;
						}
					}
				}
			}
		}
		return $quantity;
	}
	
	/**
	 * @see catalog_StockableDocument::mustSendStockAlert()
	 *
	 * @return boolean
	 */
	function mustSendStockAlert()
	{
		return false;
	}
	
	
	//TEMPLATING FUNCTION
	
	/**
	 * @see catalog_persistentdocument_product::getAdditionnalVisualArray()
	 *
	 * @return media_persistentdocument_media[]
	 */
	public function getAdditionnalVisualArray()
	{
		$result = array();
		foreach ($this->getKititemArray() as $kitItem) 
		{
			$media = $kitItem->getProduct()->getVisual();
			if ($media !== null)
			{
				$result[$media->getId()] = $media;
			}
		}
		return array_values($result);
	}
	
	/**
	 * @return string HTMLFragment
	 */
	public function getOrderLabelAsHtml()
	{
		$html = array();
		$customParams = $this->getDocumentService()->getCustomItemsInfo($this);
		if (count($customParams) > 0)
		{
			$parameters = array('catalogParam' => array('customitems' => $customParams));
		}
		else
		{
			$parameters = array();
		}
		
		$url = LinkHelper::getDocumentUrl($this, null, $parameters);
		$html[] = '<a class="link" href="'.$url.'">' . $this->getLabelAsHtml() . '</a>';
		$html[] = '<br />';
		$html[] = '<ol>';
		foreach ($this->getKititemArray() as $kititem) 
		{
			$html[] = '<li>';
			$url = LinkHelper::getDocumentUrl($kititem, null, $parameters);
			if ($url)
			{
				$html[] = '<a class="link" href="'.$url.'">' . $kititem->getTitleAsHtml() . '</a>';
			}
			else
			{
				$html[] = $kititem->getTitleAsHtml();
			}
			$html[] = '</li>';
		}
		$html[] = '</ol>';
		return implode('', $html);
	}
	
	/**
	 * @return string
	 */
	public function getCartLineKey()
	{
		$result  = array($this->getId());
		foreach ($this->getKititemArray() as $kitItem) 
		{
			$kitProduct = $kitItem->getCurrentProduct();
			$result[] = $kitProduct != null ? $kitProduct->getCartLineKey() : $kitItem->getCurrentKey();
		}
		$key = implode(',', $result);
		return $key;
	}
	
	/**
	 * @var catalog_persistentdocument_price
	 */
	private $prices = array();
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param customer_persistentdocument_customer $customer nullable
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getPrice($shop, $customer, $quantity = 1)
	{
		$key = $shop->getId() . '.' . ($customer ? $customer->getId() : '0') . '.' . $quantity;
		if (!isset($this->prices[$key]))
		{
			$targetIds = catalog_PriceService::getInstance()->convertCustomerToTargetIds($customer);
			$this->prices[$key] = $this->getDocumentService()->getPriceByTargetIds($this, $shop, $targetIds, $quantity);
		}
		return $this->prices[$key];
	}
	
	/**
	 * @var catalog_persistentdocument_price
	 */
	private $itemsPrices = array();
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param customer_persistentdocument_customer $customer nullable
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getItemsPrice($shop, $customer, $quantity = 1)
	{
		$key = $shop->getId() . '.' . ($customer ? $customer->getId() : '0') . '.' . $quantity;
		if (!isset($this->itemsPrices[$key]))
		{
			$targetIds = catalog_PriceService::getInstance()->convertCustomerToTargetIds($customer);
			$this->itemsPrices[$key] = $this->getDocumentService()->getItemsPriceByTargetIds($this, $shop, $targetIds, $quantity);
		}
		return $this->itemsPrices[$key];
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param customer_persistentdocument_customer $customer nullable
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getPriceDifference($shop, $customer, $quantity = 1)
	{
		$price1 = $this->getItemsPrice($shop, $customer, $quantity);
		$price2 = $this->getPrice($shop, $customer, $quantity);
		return catalog_PriceService::getInstance()->getPriceDifference($price1, $price2);
	}
}