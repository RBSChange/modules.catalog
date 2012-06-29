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
	 * @return string
	 */
	public function execute($request, $response, catalog_persistentdocument_alert $alert)
	{
		if ($this->isInBackofficeEdition())
		{
			return website_BlockView::NONE;
		}
		
		$product = $this->getDocumentParameter();
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		if ($shop === null || !($product instanceof catalog_persistentdocument_product) || !$product->isPublished())
		{
			return website_BlockView::NONE;
		}
		
		$request->setAttribute('product', $product);	
		$request->setAttribute('shop', $shop);
		$request->setAttribute('useCaptcha', $this->useCaptcha());
		
		$billingArea = $shop->getCurrentBillingArea();	
		$defaultPrice = $product->getPrice($shop, $billingArea, null);
		
		$type = null;
		$alertService = catalog_AlertService::getInstance();
		if (!$product->isAvailable($shop))
		{
			if ($shop->getEnableAvailabilityAlerts())
			{
				$type = catalog_AlertService::TYPE_AVAILABILITY;
			}
		}
		else if ($shop->getEnablePriceAlerts() && $defaultPrice)
		{
			
			$alert->setPriceReference($defaultPrice->getValueWithTax());
			$type = catalog_AlertService::TYPE_PRICE;
		}
		
		if ($type !== null)
		{
			$user = users_UserService::getInstance()->getCurrentFrontEndUser();
			$request->setAttribute('blockTitle', LocaleService::getInstance()->trans('m.catalog.frontoffice.notifiy-me-for-' . $type, array('ucf')));
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
	 * @return string
	 */
	public function getAddAlertInputViewName()
	{
		return 'Add';
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param catalog_persistentdocument_alert $alert
	 * @return boolean
	 */
	public function validateAddAlertInput($request, catalog_persistentdocument_alert $alert)
	{
		$rules = BeanUtils::getBeanValidationRules('catalog_persistentdocument_alert', null, array('label', 'websiteId'));
		$isOk = $this->processValidationRules($rules, $request, $alert);
		
		// Captcha is tested only for not logged-in users. 
		if ($this->useCaptcha())
		{
			$code = change_Controller::getInstance()->getContext()->getRequest()->getModuleParameter('form', 'CHANGE_CAPTCHA');
			if (!FormHelper::checkCaptchaForKey($code, 'productAlert'))
			{
				$this->addError(LocaleService::getInstance()->trans('m.catalog.frontoffice.error-captcha', array('ucf')));
				$isOk = false;
			}
			$request->setAttribute('useCaptcha', true);
		}
				
		return $isOk;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
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
			$request->setAttribute('blockTitle', LocaleService::getInstance()->trans('m.catalog.frontoffice.notifiy-me-for-' . $alert->getAlertType(), array('ucf')));
			$this->addMessage(LocaleService::getInstance()->trans('m.catalog.frontoffice.alert-already-exists', array('ucf')));
			return website_BlockView::SUCCESS;
		}
		
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$alert->generateExpirationDate($shop);
		$alert->setWebsiteId($shop->getWebsite()->getId());
		$alert->save();
		
		$request->setAttribute('blockTitle', LocaleService::getInstance()->trans('m.catalog.frontoffice.notifiy-me-for-' . $alert->getAlertType(), array('ucf')));
		$this->addMessage(LocaleService::getInstance()->trans('m.catalog.frontoffice.alert-added', array('ucf')));
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function executeRemoveAlert($request, $response)
	{
		try
		{
			$alert = DocumentHelper::getDocumentInstance($request->getParameter('alertId'));
		}
		catch (Exception $e)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::exception($e);
			}
			$this->addError(LocaleService::getInstance()->trans('m.catalog.frontoffice.unexisting-alert', array('ucf')));
			return website_BlockView::SUCCESS;
		}
		$user = users_UserService::getInstance()->getCurrentFrontEndUser();
		$request->setAttribute('alert', $alert);
		$request->setAttribute('blockTitle', LocaleService::getInstance()->trans('m.catalog.frontoffice.notifiy-me-for-' . $alert->getAlertType(), array('ucf')));
		if ($user !== null && $alert->getUserId() == $user->getId())
		{
			$alert->delete();
			$this->addMessage(LocaleService::getInstance()->trans('m.catalog.frontoffice.alert-removed', array('ucf')));
		}
		else
		{
			$this->addError(LocaleService::getInstance()->trans('m.catalog.frontoffice.not-your-alert', array('ucf')));
		}
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @return boolean
	 */
	protected function useCaptcha()
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		return ($shop->getEnableCaptchaForAlerts() && users_UserService::getInstance()->getCurrentFrontEndUser() === null);
	}
}