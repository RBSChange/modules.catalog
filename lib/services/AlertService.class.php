<?php
/**
 * catalog_AlertService
 * @package modules.catalog
 */
class catalog_AlertService extends f_persistentdocument_DocumentService
{
	const TYPE_AVAILABILITY = 'availability';
	const TYPE_PRICE = 'price';
	
	/**
	 * @var catalog_AlertService
	 */
	private static $instance;

	/**
	 * @return catalog_AlertService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @return catalog_persistentdocument_alert
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/alert');
	}

	/**
	 * Create a query based on 'modules_catalog/alert' model.
	 * Return document that are instance of modules_catalog/alert,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/alert');
	}
	
	/**
	 * Create a query based on 'modules_catalog/alert' model.
	 * Only documents that are strictly instance of modules_catalog/alert
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/alert', false);
	}
	
	/**
	 * @param users_persistentoducment_frontenduser $user
	 * @param catalog_persistentdocument_product $product
	 * @param string $type
	 * @return boolean
	 */
	public function hasAlert($user, $product, $type)
	{
		return $this->getAlert($user, $product, $type) !== null;
	}
	
	/**
	 * @param users_persistentoducment_frontenduser $user
	 * @param catalog_persistentdocument_product $product
	 * @param string $type
	 * @return catalog_persistentdocument_alert
	 */
	public function getAlert($user, $product, $type)
	{
		return $this->createQuery()->add(Restrictions::published())
			->add(Restrictions::eq('alertType', $type))
			->add(Restrictions::eq('productId', $product->getId()))
			->add(Restrictions::eq('userId', $user->getId()))->findUnique();
	}
	
	/**
	 * @param users_persistentoducment_frontenduser $user
	 * @return catalog_persistentdocument_alert[]
	 */
	public function getPublishedByUser($user)
	{
		return $this->createQuery()->add(Restrictions::published())->add(Restrictions::eq('userId', $user->getId()))->find();
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return catalog_persistentdocument_alert[]
	 */
	public function getPublishedByProduct($product)
	{
		return $this->createQuery()->add(Restrictions::published())->add(Restrictions::eq('productId', $product->getId()))->find();
	}
	
	/**
	 * @param users_persistentoducment_frontenduser $user
	 * @param integer[] $productIds
	 * @return boolean
	 */
	public function hasPublishedByUserAndProductIds($user, $productIds)
	{
		$row = $this->createQuery()->add(Restrictions::published())->add(Restrictions::eq('userId', $user->getId()))
			->add(Restrictions::in('productId', $productIds))->setProjection(Projections::rowCount('count'))->findUnique();
		return $row['count'] > 0;
	}
		
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return integer
	 */
	public function getPublishedCountByProduct($product)
	{
		$row = $this->createQuery()->add(Restrictions::published())->add(Restrictions::eq('productId', $product->getId()))->setProjection(Projections::rowCount('count'))->findUnique();
		return $row['count'];
	}
	
	/**
	 * @return integer[]
	 */
	public function getPendingEmails()
	{
		$query = $this->createQuery()->add(Restrictions::published())->add(Restrictions::eq('pending', true))->setProjection(Projections::property('email'));
		return array_unique($query->findColumn('email'));
	}
	
	/**
	 * @param string $email
	 */
	public function sendAlertsForEmail($email)
	{
		$query = $this->createQuery()->add(Restrictions::eq('pending', true))
			->add(Restrictions::published())
			->add(Restrictions::eq('email', $email));
			
		$allAlerts = $query->find();
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' ' . count($allAlerts) . ' for email ' . $email);
		}
		
		$alertsByWebsiteId = array();
		foreach ($allAlerts as $alert) 
		{
			$product = $alert->getProduct();
			if ($product instanceof catalog_persistentdocument_product) 
			{
				$alertsByWebsiteId[$alert->getWebsiteId()][] = $alert;
			}
		}
		
		$ns = notification_NotificationService::getInstance();
		foreach ($alertsByWebsiteId as $alerts)
		{
			if (count($alerts) == 1)
			{
				$notification = $this->getNotificationByAlert(f_util_ArrayUtils::firstElement($alerts));
			}
			else
			{
				$alert = f_util_ArrayUtils::firstElement($alerts);
				$notification = $ns->getConfiguredByCodeName('modules_catalog/severalalerts', $alert->getWebsiteId(), $alert->getLang());
			}
			
			if ($notification instanceof notification_persistentdocument_notification)
			{
				$notification->setSendingModuleName('catalog');
				$ns->sendNotificationCallback($notification, change_MailService::getInstance()->getRecipientsArray(array($email)), array($this, 'getNotificationParameters'), $alerts);
				
			}
		}
		
		if (count($allAlerts))
		{
			$this->createQuery()->add(Restrictions::in('id', DocumentHelper::getIdArrayFromDocumentArray($allAlerts)))->delete();
		}
	}
	
	/**
	 * @param catalog_persistentdocument_alert[] $alerts
	 * @return array<string, string>
	 */
	public function getNotificationParameters($alerts)
	{
		if (count($alerts) > 1)
		{
			$alertList = array();
			foreach ($alerts as $alert)
			{
				$tempReplacements = $this->getReplacementsForAlert($alert);
				$text = $this->getNotificationByAlert($alert)->getBody();
				$alertList[] = $this->applyReplacements($text, $tempReplacements);
			}
			$replacements = array('alertList' => implode("<br />\n<br />\n", $alertList));			
		}
		else if (count($alerts) == 1)
		{
			$alert = f_util_ArrayUtils::firstElement($alerts);
			$replacements = $this->getReplacementsForAlert($alert);
		}
		return $replacements;
	}
	
	/**
	 * @param catalog_persistentdocument_alert $alert
	 * @return array<string, string>
	 */
	private function getReplacementsForAlert($alert)
	{
		$product = $alert->getProduct();
		$shop = $alert->getShop();
		$price = $product->getPrice($shop, null);
		$productUrl = LinkHelper::getDocumentUrl($product, $alert->getLang(), array('catalogParam' => array('shopId' => $shop->getId())));
		$productLabel = $product->getLabelAsHtml();
		$productLink = '<a href="'.$productUrl.'" class="link">'.$productLabel.'</a>';
		$replacements = array(
			'productPriceWithTax' => $price->getFormattedValueWithTax(),
			'productPriceWithoutTax' => $price->getFormattedValueWithoutTax(),
			'productLabel' => $productLabel,
			'productDescription' => $product->getDescriptionAsHtml(),
			'productUrl' => $productUrl,
			'productLink' => $productLink
		);
		return $replacements;
	}
	
	/**
	 * Copied from notification_NotificationService.
	 * @param string $string
	 * @param array<string> $replacements
	 * @return string
	 */
	private function applyReplacements($string, $replacements)
	{
		if (!$string)
		{
			return '';
		}

		if(is_array($replacements))
		{
			foreach ($replacements as $key => $value)
			{
				if (!is_array($value))
				{
					$string = str_replace('{' . $key . '}',	$value, $string);
				}
				else if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . ' following value has invalid type and is skipped: ' . var_export($value, true));			
				}
			}
		}

		// Remove the not-replaced elements.
		$string = preg_replace('#\{(.*)\}#', '-', $string);
		return $string;
	}

	/**
	 * @param catalog_persistentodcument_alert $alert
	 * @return notification_persistentodcument_notification
	 */
	public function getNotificationByAlert($alert)
	{
		return notification_NotificationService::getInstance()->getConfiguredByCodeName('modules_catalog/'.$alert->getAlertType().'alert', $alert->getWebsiteId(), $alert->getLang());
	}
	
	/**
	 * @return integer[]
	 */
	public function purgeExpiredAlerts()
	{
		return $this->createQuery()->add(Restrictions::ne('publicationstatus', 'PUBLICATED'))->delete();
	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId)
	{
		$document->setInsertInTree(false);
		
		if (!$document->getLabel())
		{
			$user = $document->getUser();
			if ($user !== null)
			{
				$document->setLabel(f_Locale::translate('&modules.catalog.document.alert.Label-patern-user;', array('productId' => $document->getProductId(), 'user' => $user->getFullname())));	
			}
			else 
			{
				$document->setLabel(f_Locale::translate('&modules.catalog.document.alert.Label-patern-email;', array('productId' => $document->getProductId(), 'email' => $document->getEmail())));
			}
		}
		
		$document->setPassword(f_util_StringUtils::randomString());
	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		$this->sendCreationNotification($document);		
	}
	
	/**
	 * @param catalog_persistentdocument_alert $alert
	 */
	protected function sendCreationNotification($alert)
	{
		$ns = notification_NotificationService::getInstance();
		$configuredNotif = $ns->getConfiguredByCodeName('modules_catalog/newalert', $alert->getWebsiteId(), $alert->getLang());
		if ($configuredNotif instanceof notification_persistentdocument_notification)
		{
			$configuredNotif->setSendingModuleName('catalog');
			$callback = array($this, 'getCreationNotificationParameters');
			$ns->sendNotificationCallback($configuredNotif, change_MailService::getInstance()->getRecipientsArray(array($alert->getEmail())), $callback, $alert);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_alert $alert
	 * @return array
	 */
	public function getCreationNotificationParameters($alert)
	{
		$params = $this->getReplacementsForAlert($alert);
		
		$catalogParam = array('alertId' => $alert->getId(), 'website_BlockAction_submit' => array('productAlertManagement' => array('RemoveAlert' => 'true')));
		$catalogParam['password'] = $alert->getPassword();

		$removeAlertParameters = array('catalogParam' => $catalogParam);
		$removeAlertUrl = LinkHelper::getTagUrl('contextual_website_website_modules_catalog_product-alert-management', null, $removeAlertParameters);
		$removeAlertLink = '<a href="'.$removeAlertUrl.'" class="link">'.LocaleService::getInstance()->transFO('m.catalog.frontoffice.remove-alert').'</a>';
		$params['removeAlertUrl'] = $removeAlertUrl;
		$params['removeAlertLink'] = $removeAlertLink;
		$params['alertEndDate'] = date_Formatter::toDefaultDateTime($alert->getUIEndpublicationdate());
				
		return $params;
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 */
	public function setPendingForAvailability($document)
	{
		try 
		{
			$this->tm->beginTransaction();
			
			$query = $this->createQuery()
				->add(Restrictions::published())
				->add(Restrictions::eq('productId', $document->getProduct()->getId()))
				->add(Restrictions::eq('pending', false))
				->add(Restrictions::eq('alertType', self::TYPE_AVAILABILITY));
				
			foreach ($query->find() as $alert)
			{
				$alert->setPending(true);
				$this->pp->updateDocument($alert);
			}				
			
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}	
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 */
	public function setPendingForPrice($document)
	{
		try 
		{
			$this->tm->beginTransaction();
			
			$query = $this->createQuery()
				->add(Restrictions::published())
				->add(Restrictions::eq('productId', $document->getProduct()->getId()))
				->add(Restrictions::eq('pending', false))
				->add(Restrictions::eq('alertType', self::TYPE_PRICE))
				->add(Restrictions::gt('priceReference', $document->getPrice()));
						
			foreach ($query->find() as $alert)
			{
				$alert->setPending(true);
				$this->pp->updateDocument($alert);
			}
			
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}		
	}
}