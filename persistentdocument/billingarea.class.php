<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_billingarea
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_billingarea extends catalog_persistentdocument_billingareabase 
{
	
	/**
	 * @param float|null $value
	 * @param string $lang
	 * @return string
	 */
	public function formatPrice($value, $lang = null)
	{
		return catalog_PriceFormatter::getInstance()->format($value, $this->getCurrency()->getCode(), $lang, $this->getCurrencyPosition());
	}
	
	/**
	 * @param string $lang
	 * @return string
	 */
	public function getPriceFormat($lang = null)
	{	
		return catalog_PriceFormatter::getInstance()->getFormat($this->getCurrency()->getCode(), $lang, $this->getCurrencyPosition());
	}
	
	/**
	 * @return string
	 */
	public function getCurrencyCode()
	{
		return $this->getCurrency()->getCode();
	}	
	
	//BO Edition
	public function getTaxGridJSON()
	{
		$result = array();
		foreach (catalog_TaxService::getInstance()->getByBillingAreaId($this->getId()) as $tax)
		{
			/* @var $tax catalog_persistentdocument_tax */
			$result[] = array(
				'id' => $tax->getId(),
				'taxtype' => str_replace('/', '_', $tax->getDocumentModelName()),
				'billingareaid' => $this->getId(),
				'label' => $tax->getLabel(),
				'taxzone' => $tax->getTaxZone(),
				'taxcategory' => $tax->getTaxCategory(),
				'rate' => $tax->getRate(),
			);
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
	private $newTaxZone = false;
	
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
		if ($this->newTaxZone === false)
		{
			$this->newTaxZone = ($this->getZone()) ? $this->getZone()->getCode() : null;
		}
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
		$this->newTaxZone = false;
		$this->newTaxLabel = null;
	}
}