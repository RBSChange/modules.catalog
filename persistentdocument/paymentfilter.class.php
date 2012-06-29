<?php
/**
 * catalog_persistentdocument_paymentfilter
 * @package catalog.persistentdocument
 */
class catalog_persistentdocument_paymentfilter extends catalog_persistentdocument_paymentfilterbase 
{
	
	//Back office editor

	/**
	 * @return string
	 */
	public function getBillingAreaColumnLabel()
	{
		$ba = $this->getBillingArea();
		return $ba ? $ba->getTreeNodeLabel() : '-';
	}
}