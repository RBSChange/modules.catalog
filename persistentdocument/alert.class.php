<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_alert
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_alert extends catalog_persistentdocument_alertbase 
{
	/**
	 * @return users_persistentdocument_frontenduser
	 */
	public function hasUser()
	{
		return $this->getUser() !== null;
	}
	
	/**
	 * @return users_persistentdocument_frontenduser
	 */
	public function getUser()
	{
		$userId = $this->getUserId();
		if ($userId !== null)
		{
			try 
			{
				return DocumentHelper::getDocumentInstance($userId, 'modules_users/frontenduser');
			}
			catch (Exception $e)
			{
				// User doesn't exist any more...
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . ' ' . $e->getMessage());
				}
			}
		}
		return null;
	}
	
	/**
	 * @return catalog_persistentdocument_product
	 */
	public function getProduct()
	{
		$productId = $this->getProductId();
		if ($productId !== null)
		{
			try 
			{
				return DocumentHelper::getDocumentInstance($productId, 'modules_catalog/product');
			}
			catch (Exception $e)
			{
				// Product doesn't exist any more...
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . ' ' . $e->getMessage());
				}
			}
		}
		return null;
	}
	
	/**
	 * @return catalog_persistentdocument_shop
	 */
	public function getShop()
	{
		return catalog_ShopService::getInstance()->getPublishedByWebsiteId($this->getWebsiteId());
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 */
	public function generateExpirationDate($shop)
	{
		$duration = $shop->{'get'.ucfirst($this->getAlertType()).'AlertsDuration'}();
		$coef = 1;
		switch (substr($duration, -1))
		{
			case 'd': $field = date_Calendar::DAY; break;
			case 'w': $field = date_Calendar::DAY; $coef = 7; break;
			case 'm': $field = date_Calendar::MONTH; break;
			case 'y': $field = date_Calendar::YEAR; break;
			default: $field = date_Calendar::MONTH; break;
		}
		$date = date_Calendar::getInstance();
		$date->add($field, $coef*intval(substr($duration, 0, -1)));
		$this->setEndpublicationdate($date->toString());
	}
	
	/**
	 * @return string
	 */
	public function getFomatedExpirationDate()
	{
		return date_DateFormat::format($this->getEndpublicationdate(), f_Locale::translate('&framework.date.date.localized-data-format;'));
	}

	/**
	 * @return string;
	 */
	public function getBlockTitle()
	{
		return f_Locale::translate('&modules.catalog.frontoffice.Notifiy-me-for-' . $this->getAlertType() . ';');
	}
	
	/**
	 * @return string
	 */
	public function getAlertTypeLabel()
	{
		return f_Locale::translate('&modules.catalog.frontoffice.Alert-on-' . $this->getAlertType() . '-for;');
	}
	
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
}