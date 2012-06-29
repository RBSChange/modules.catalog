<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_alert
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_alert extends catalog_persistentdocument_alertbase 
{
	/**
	 * @return users_persistentdocument_user
	 */
	public function hasUser()
	{
		return $this->getUser() !== null;
	}
	
	/**
	 * @return users_persistentdocument_user
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
		return catalog_ShopService::getInstance()->getDefaultByWebsiteId($this->getWebsiteId());
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
		return date_Formatter::toDefaultDateTime($this->getUIEndpublicationdate());
	}

	/**
	 * @return string;
	 */
	public function getBlockTitle()
	{
		return LocaleService::getInstance()->trans('m.catalog.frontoffice.notifiy-me-for-' . $this->getAlertType(), array('ucf'));
	}
	
	/**
	 * @return string
	 */
	public function getAlertTypeLabel()
	{
		return LocaleService::getInstance()->trans('m.catalog.frontoffice.alert-on-' . $this->getAlertType() . '-for', array('ucf'));
	}
}