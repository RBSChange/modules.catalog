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
	 * @return catalog_persistentdocument_billingarea
	 */
	public function getDefaultBillingArea()
	{
		return $this->getBillingAreas(0);
	}
	
	/**
	 * @var catalog_persistentdocument_billingarea
	 */
	private $currentBillingArea;
	
	/**
	 * @param boolean $refresh
	 * @return catalog_persistentdocument_billingarea
	 */
	public function getCurrentBillingArea($refresh = false)
	{
		if ($this->currentBillingArea === null || $refresh)
		{
			$this->setCurrentBillingArea(catalog_BillingareaService::getInstance()->getCurrentBillingAreaForShop($this, $refresh));
		}
		return $this->currentBillingArea;
	}
	
	/**
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @return catalog_persistentdocument_billingarea|NULL previous current billingArea
	 */
	public function setCurrentBillingArea($billingArea)
	{
		$pba = $this->currentBillingArea;
		if ($billingArea instanceof catalog_persistentdocument_billingarea)
		{
			$this->currentBillingArea = $billingArea;
		}
		else
		{
			$this->currentBillingArea = null;
		}
		return $pba;
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
		
	/**
	 * @var integer[]
	 */
	private $updatedShelfArray;
	
	/**
	 * @return integer[]
	 */
	public function getUpdatedShelfArray()
	{
		return $this->updatedShelfArray;
	}

	/**
	 * @param integer[] $updatedShelfArray
	 */
	public function setUpdatedShelfArray($updatedShelfArray)
	{
		$this->updatedShelfArray = $updatedShelfArray;
	}
	
	// DEPRECATED
	
	/**
	 * @deprecated
	 */
	public function getBoTaxZone()
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		if ($this->getDefaultBillingArea()->getBoEditWithTax())
		{
			return $this->getDefaultBillingArea()->getDefaultZone();
		}
		return null;
	}	
	
	/**
	 * @deprecated
	 */
	public function getCurrencySymbol()
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		return $this->getDefaultBillingArea()->getCurrency()->getSymbol();
	}
	
	/**
	 * @deprecated
	 */
	public function getCurrencyCode()
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		return $this->getDefaultBillingArea()->getCurrencyCode();
	}	
	
	/**
	 * @deprecated
	 */
	public function formatPrice($value)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		return $this->getCurrentBillingArea()->formatPrice($value);
	}
	
	/**
	 * @deprecated
	 */
	public function formatTaxRate($taxRate)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		return catalog_TaxService::getInstance()->formatRate($taxRate);
	}

}