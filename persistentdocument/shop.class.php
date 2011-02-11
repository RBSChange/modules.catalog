<?php
/**
 * catalog_persistentdocument_shop
 * @package modules.catalog
 */
class catalog_persistentdocument_shop extends catalog_persistentdocument_shopbase 
{
	/**
	 * @var f_persistentdocument_PersistentDocument $parent instance of topic or website
	 */
	private $parent = null;

	/**
	 * @return f_persistentdocument_PersistentDocument instance of topic or website
	 */
	public function getMountParent()
	{
		if ($this->parent !== null)
		{
			return $this->parent;
		}
		$topic = $this->getTopic();
		if ($topic !== null)
		{
			return $topic->getDocumentService()->getParentOf($topic);
		}
		return null;
	}
	
	/**
	 * @return Integer
	 */
	public function getMountParentId()
	{
		$parent = $this->getMountParent();
		return ($parent === null) ? null : $parent->getId();
	}
	
	/**
	 * @param Integer $parentId
	 */
	public function setMountParentId($parentId)
	{
		$parent = null;
		if ($parentId !== null)
		{
			$parent = DocumentHelper::getDocumentInstance($parentId);
		}
		$this->parent = $parent;
		$this->setModificationdate(null);
	}

	/**
	 * @return boolean
	 */
	public function isValid()
	{
		if (!parent::isValid())
		{
			return false;
		}
		
		if (!$this->getIsDefault())
		{
			return true;
		}
		
		// Ensure that there may be only one published default shop by website on a given time period.
		$query = catalog_ShopService::getInstance()->createQuery()
			->add(Restrictions::ne('id', $this->getId()))
			->add(Restrictions::eq('website', $this->getWebsite()))
			->add(Restrictions::eq('isDefault', true));
	
		$endDate = $this->getEndpublicationdate();
		if ($endDate !== null)
		{
			$query->add(Restrictions::orExp(Restrictions::isEmpty('startpublicationdate'), Restrictions::lt('startpublicationdate', $endDate)));
		}
		$startDate = $this->getStartpublicationdate();
		if ($startDate !== null)
		{
			$query->add(Restrictions::orExp(Restrictions::isEmpty('endpublicationdate'), Restrictions::gt('endpublicationdate', $startDate)));
		}

		if ($query->findUnique() !== null)
		{
			$message = LocaleService::getInstance()->transBO('modules.catalog.document.shop.exception.publication-period-conflict', array('ucf'));
			$this->validationErrors->rejectValue('previewStartDate', $message);
			return false;
		}
		return true;
	}
	
	/**
	 * @return String
	 * @throw catalog_Exception
	 */
	public function getCurrencySymbol()
	{
		return catalog_ShopService::getInstance()->getCurrencySymbol($this);
	}
		
	/**
	 * @param double $value
	 * @return string
	 */
	public function formatPrice($value)
	{
		return catalog_PriceFormatter::getInstance()->format($value, $this->getCurrencyCode());
	}
	
	/**
	 * @param double $value
	 * @return string
	 */	
	public function formatTaxRate($taxRate)
	{
		return catalog_PriceHelper::formatTaxRate($taxRate);
	}
	
	/**
	 * @return String[]
	 */
	public function getNewTranslationLangs()
	{
		$langs = array();
		foreach ($this->getI18nInfo()->getLangs() as $lang)
		{
			if ($this->getI18nObject($lang)->isNew())
			{
				$langs[] = $lang;
			}
		}
		return $langs;
	}
	
	//BO Edition
	
	public function getTaxGridJSON()
	{
		$result = array();
		foreach (catalog_TaxService::getInstance()->getByShop($this) as $tax) 
		{
			if ($tax instanceof catalog_persistentdocument_tax)
			{
				$result[] = array(
					'id' => $tax->getId(),
					'taxtype' => str_replace('/', '_', $tax->getDocumentModelName()),
					'shopid' => $this->getId(),
				    'shoptype' => str_replace('/', '_', $this->getDocumentModelName()),
				    'label' => $tax->getLabel(),
					'taxzone' => $tax->getTaxZone(),
					'taxcategory' => $tax->getTaxCategory(),
				    'rate' => $tax->getRate(),
				);
			}
		}
		return JsonService::getInstance()->encode($result);
	}
	
	/**
	 * @var string	 
	 */
	private $newTaxLabel;

	/**
	 * @var string	 
	 */
	private $newTaxZone;
	
	/**
	 * @var string	 
	 */
	private $newTaxCategory;

	/**
	 * @var double	 
	 */
	private $newTaxRate;
	
	/**
	 * @return string
	 */
	public function getNewTaxLabel()
	{
		return $this->newTaxLabel;
	}

	/**
	 * @return string
	 */
	public function getNewTaxZone()
	{
		return $this->newTaxZone;
	}
	
	/**
	 * @return string
	 */
	public function getNewTaxCategory()
	{
		return $this->newTaxCategory;
	}

	/**
	 * @return double
	 */
	public function getNewTaxRate()
	{
		return $this->newTaxRate;
	}

	/**
	 * @param string $newTaxLabel
	 */
	public function setNewTaxLabel($newTaxLabel)
	{
		$this->newTaxLabel = $newTaxLabel;
		$this->setModificationdate(null);
	}

	/**
	 * @param string $newTaxZone
	 */
	public function setNewTaxZone($newTaxZone)
	{
		$this->newTaxZone = $newTaxZone;
	}
	
	/**
	 * @param string $newTaxCategory
	 */
	public function setNewTaxCategory($newTaxCategory)
	{
		$this->newTaxCategory = $newTaxCategory;
	}

	/**
	 * @param double $newTaxRate
	 */
	public function setNewTaxRate($newTaxRate)
	{
		$this->newTaxRate = $newTaxRate;
	}
	
	public function resetNewTaxInfos()
	{
		$this->newTaxRate = null;
		$this->newTaxCategory = null;
		$this->newTaxZone = null;
		$this->newTaxLabel = null;		
	}
}