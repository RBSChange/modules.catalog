<?php
/**
 * catalog_BlockProductAlertAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockProductAlertAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response, catalog_persistentdocument_alert $alert)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}
		
		$product = $this->getDocumentParameter();
		if ($product === null || !($product instanceof catalog_persistentdocument_product) || !$product->isPublished())
		{
			return website_BlockView::NONE;
		}
		
		$request->setAttribute('product', $product);
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$request->setAttribute('shop', $shop);
		
		$type = null;
		$alertService = catalog_AlertService::getInstance();
		if (!$product->isAvailable($shop))
		{
			if ($shop->getEnableAvailabilityAlerts())
			{
				$type = catalog_AlertService::TYPE_AVAILABILITY;
			}
		}
		else if ($shop->getEnablePriceAlerts())
		{
			$alert->setPriceReference($product->getPrice($shop, null)->getValueWithTax());
			$type = catalog_AlertService::TYPE_PRICE;
		}
		
		if ($type !== null)
		{
			$user = users_UserService::getInstance()->getCurrentFrontEndUser();
			$request->setAttribute('blockTitle', f_Locale::translate('&modules.catalog.frontoffice.Notifiy-me-for-' . $type . ';'));
			if ($user !== null)
			{
				$existingAlert = $alertService->getAlert($user, $product, $type);
				if ($existingAlert !== null)
				{
					$request->setAttribute('alert', $existingAlert);
					return 'Remove';
				}
			}
			
			$alert->setProductId($product->getId());
			$alert->setAlertType($type);
			if ($user !== null)
			{
				$alert->setUserId($user->getId());
				$alert->setEmail($user->getEmail());
			}
			$request->setAttribute('alert', $alert);
			return 'Add';
		}
		
		return website_BlockView::NONE;
	}
	
	/**
	 * @return String
	 */
	public function getAddAlertInputViewName()
	{
		return 'Add';
	}	
	
	/**
	 * @param f_mvc_Request $request
	 * @return string[]
	 */
	public function getAddAlertInputValidationRules($request)
	{
		return BeanUtils::getBeanValidationRules('catalog_persistentdocument_alert', null, array('label', 'websiteId'));
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executeAddAlert($request, $response, catalog_persistentdocument_alert $alert)
	{
		$user = users_UserService::getInstance()->getCurrentFrontEndUser();
		if ($user !== null)
		{
			$alert->setUserId($user->getId());
			$alert->setEmail($user->getEmail());
		}
		
		$existingAlert = catalog_AlertService::getInstance()->createQuery()->add(Restrictions::published())->add(Restrictions::eq('email', $alert->getEmail()))->add(Restrictions::eq('productId', $alert->getProductId()))->add(Restrictions::eq('alertType', $alert->getAlertType()))->findUnique();
		if ($existingAlert !== null)
		{
			$request->setAttribute('blockTitle', f_Locale::translate('&modules.catalog.frontoffice.Notifiy-me-for-' . $alert->getAlertType() . ';'));
			$this->addMessage(f_Locale::translate('&modules.catalog.frontoffice.Alert-already-exists;'));
			return website_BlockView::SUCCESS;
		}
		
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$alert->generateExpirationDate($shop);
		$alert->setWebsiteId($shop->getWebsite()->getId());
		$alert->save();
		
		$request->setAttribute('blockTitle', f_Locale::translate('&modules.catalog.frontoffice.Notifiy-me-for-' . $alert->getAlertType() . ';'));
		$this->addMessage(f_Locale::translate('&modules.catalog.frontoffice.Alert-added;'));
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executeRemoveAlert($request, $response)
	{
		try
		{
			$alert = DocumentHelper::getDocumentInstance($request->getParameter('alertId'));
		}
		catch (Exception $e)
		{
			$this->addError(f_Locale::translate('&modules.catalog.frontoffice.Unexisting-alert;'));
			return website_BlockView::SUCCESS;
		}
		$user = users_UserService::getInstance()->getCurrentFrontEndUser();
		$request->setAttribute('alert', $alert);
		$request->setAttribute('blockTitle', f_Locale::translate('&modules.catalog.frontoffice.Notifiy-me-for-' . $alert->getAlertType() . ';'));
		if ($user !== null && $alert->getUserId() == $user->getId())
		{
			$alert->delete();
			$this->addMessage(f_Locale::translate('&modules.catalog.frontoffice.Alert-removed;'));
		}
		else
		{
			$this->addError(f_Locale::translate('&modules.catalog.frontoffice.Not-your-alert;'));
		}
		return website_BlockView::SUCCESS;
	}
}