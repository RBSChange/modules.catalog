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
			self::$instance = self::getServiceClassInstance(get_class());
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
		$query = $this->createQuery()->add(Restrictions::eq('pending', true))->add(Restrictions::published())->add(Restrictions::eq('email', $email));
		$alerts = $query->find();
		Framework::info(__METHOD__ . ' ' . count($alerts) . ' for email ' . $email);
		$ns = notification_NotificationService::getInstance();
		if (count($alerts) == 1)
		{
			$alert = f_util_ArrayUtils::firstElement($alerts);
			$replacements = $this->getReplacementsForAlert($alert);			
			$notification = $this->getNotificationByAlert($alert);
		}
		else
		{
			$alertList = array();
			foreach ($alerts as $alert)
			{
				$tempReplacements = $this->getReplacementsForAlert($alert);
				$text = $this->getNotificationByAlert($alert)->getBody();
				$alertList[] = $this->applyReplacements($text, $tempReplacements);
			}
			$replacements = array('alertList' => implode("<br />\n<br />\n", $alertList));
			$notification = $ns->getByCodeName('modules_catalog/severalalerts');			
		}
		
		$recipients = new mail_MessageRecipients();
		$recipients->setTo($email);
		$ns->send($notification, $recipients, $replacements, 'catalog');
		
		$this->createQuery()->add(Restrictions::in('id', DocumentHelper::getIdArrayFromDocumentArray($alerts)))->delete();
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
		$productUrl = LinkHelper::getDocumentUrl($product);
		$prpductLabel = $product->getLabelAsHtml();
		$productLink = '<a href="'.$productUrl.'" class="link">'.$prpductLabel.'</a>';
		$replacements = array(
			'productPriceWithTax' => $price->getFormattedValueWithTax(),
			'productPriceWithoutTax' => $price->getFormattedValueWithoutTax(),
			'productLabel' => $prpductLabel,
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
		return notification_NotificationService::getInstance()->getByCodeName('modules_catalog/'.$alert->getAlertType().'alert', $alert->getWebsiteId());
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
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
//	protected function preSave($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId = null)
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
		
		if (!$document->hasUser())
		{
			$document->setPassword(f_util_StringUtils::randomString());
		}
	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId = null)
	{
		$this->sendCreationNotification($document);		
	}
	
	/**
	 * @param catalog_persistentdocument_alert $alert
	 */
	protected function sendCreationNotification($alert)
	{
		$product = $alert->getProduct();
		$productUrl = LinkHelper::getDocumentUrl($product);
		$prpductLabel = $product->getLabelAsHtml();
		$productLink = '<a href="'.$productUrl.'" class="link">'.$prpductLabel.'</a>';
		
		$catalogParam = array('alertId' => $alert->getId(), 'website_BlockAction_submit' => array('productAlertManagement' => array('RemoveAlert' => 'true')));
		if (!$alert->hasUser())
		{
			$catalogParam['password'] = $alert->getPassword();
		}
		$removeAlertParameters = array('catalogParam' => $catalogParam);
		$removeAlertUrl = LinkHelper::getTagUrl('contextual_website_website_modules_catalog_product-alert-management', null, $removeAlertParameters);
		$removeAlertLink = '<a href="'.$removeAlertUrl.'" class="link">'.f_Locale::translate('&modules.catalog.frontoffice.remove-alert;').'</a>';
		
		$replacements = array(
			'productLabel' => $prpductLabel,
			'productDescription' => $product->getDescriptionAsHtml(),
			'productUrl' => $productUrl,
			'productLink' => $productLink,
			'removeAlertUrl' => $removeAlertUrl,
			'removeAlertLink' => $removeAlertLink,
			'alertEndDate' => date_DateFormat::format($alert->getEndpublicationdate())
		);
		
		$ns = notification_NotificationService::getInstance();
		$recipients = new mail_MessageRecipients();
		$recipients->setTo($alert->getEmail());
		$ns->send($ns->getByCodeName('modules_catalog/newalert'), $recipients, $replacements, 'catalog');
	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preUpdate($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postUpdate($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postSave($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @return void
	 */
//	protected function preDelete($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @return void
	 */
//	protected function preDeleteLocalized($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @return void
	 */
//	protected function postDelete($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @return void
	 */
//	protected function postDeleteLocalized($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
//	public function isPublishable($document)
//	{
//		$result = parent::isPublishable($document);
//		return $result;
//	}


	/**
	 * Methode à surcharger pour effectuer des post traitement apres le changement de status du document
	 * utiliser $document->getPublicationstatus() pour retrouver le nouveau status du document.
	 * @param catalog_persistentdocument_alert $document
	 * @param String $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
//	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
//	{
//	}

	/**
	 * Correction document is available via $args['correction'].
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Array<String=>mixed> $args
	 */
//	protected function onCorrectionActivated($document, $args)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagAdded($document, $tag)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagRemoved($document, $tag)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_alert $fromDocument
	 * @param f_persistentdocument_PersistentDocument $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedFrom($fromDocument, $toDocument, $tag)
//	{
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $fromDocument
	 * @param catalog_persistentdocument_alert $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedTo($fromDocument, $toDocument, $tag)
//	{
//	}

	/**
	 * Called before the moveToOperation starts. The method is executed INSIDE a
	 * transaction.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $destId
	 */
//	protected function onMoveToStart($document, $destId)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @param Integer $destId
	 * @return void
	 */
//	protected function onDocumentMoved($document, $destId)
//	{
//	}

	/**
	 * this method is call before saving the duplicate document.
	 * If this method not override in the document service, the document isn't duplicable.
	 * An IllegalOperationException is so launched.
	 *
	 * @param catalog_persistentdocument_alert $newDocument
	 * @param catalog_persistentdocument_alert $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function preDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//		throw new IllegalOperationException('This document cannot be duplicated.');
//	}

	/**
	 * this method is call after saving the duplicate document.
	 * $newDocument has an id affected.
	 * Traitment of the children of $originalDocument.
	 *
	 * @param catalog_persistentdocument_alert $newDocument
	 * @param catalog_persistentdocument_alert $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function postDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//	}

	/**
	 * Returns the URL of the document if has no URL Rewriting rule.
	 *
	 * @param catalog_persistentdocument_alert $document
	 * @param string $lang
	 * @param array $parameters
	 * @return string
	 */
//	public function generateUrl($document, $lang, $parameters)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @return integer | null
	 */
//	public function getWebsiteId($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @return website_persistentdocument_page | null
	 */
//	public function getDisplayPage($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
//	public function getResume($document, $forModuleName, $allowedSections = null)
//	{
//		$resume = parent::getResume($document, $forModuleName, $allowedSections);
//		return $resume;
//	}

	/**
	 * @param catalog_persistentdocument_alert $document
	 * @param string $bockName
	 * @return array with entries 'module' and 'template'. 
	 */
//	public function getSolrserachResultItemTemplate($document, $bockName)
//	{
//		return array('module' => 'catalog', 'template' => 'Catalog-Inc-AlertResultDetail');
//	}
}