<?php
/**
 * catalog_CurrencyService
 * @package modules.catalog
 */
class catalog_CurrencyService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_CurrencyService
	 */
	private static $instance;
	
	/**
	 * @return catalog_CurrencyService
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
	 * @return catalog_persistentdocument_currency
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/currency');
	}
	
	/**
	 * Create a query based on 'modules_catalog/currency' model.
	 * Return document that are instance of modules_catalog/currency,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/currency');
	}
	
	/**
	 * Create a query based on 'modules_catalog/currency' model.
	 * Only documents that are strictly instance of modules_catalog/currency
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/currency', false);
	}
	
	private $currencySymbols;
	
	/**
	 * @return String[]
	 */
	public function getCurrencySymbolsArray()
	{
		if ($this->currencySymbols === null)
		{
			$this->currencySymbols = array();
			$lines = catalog_CurrencyService::getInstance()->createQuery()->add(Restrictions::published())->setProjection(Projections::property('code', 'code'), Projections::property('symbol', 'symbol'))->find();
			foreach ( $lines as $line )
			{
				$this->currencySymbols[$line['code']] = $line['symbol'];
			}
		}
		return $this->currencySymbols;
	}
	
	/**
	 * 
	 */
	public function getCodeById($id)
	{
		$codes = $this->getCurrencyCodesArray();
		return isset($codes[$id]) ? $codes[$id] : null;
	}

	private $currencyCodes;
	
	protected function getCurrencyCodesArray()
	{
		if ($this->currencyCodes === null)
		{
			$this->currencyCodes = array();
			$lines = catalog_CurrencyService::getInstance()->createQuery()->add(Restrictions::published())->setProjection(Projections::property('code', 'code'), Projections::property('id', 'id'))->find();
			foreach ( $lines as $line )
			{
				$this->currencyCodes[$line['id']] = $line['code'];
			}
		}
		return $this->currencyCodes;
	}
/**
 * @param catalog_persistentdocument_currency $document
 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
 * @return void
 */
//	protected function preSave($document, $parentNodeId)
//	{
//
//	}


/**
 * @param catalog_persistentdocument_currency $document
 * @param Integer $parentNodeId Parent node ID where to save the document.
 * @return void
 */
//	protected function preInsert($document, $parentNodeId)
//	{
//	}


/**
 * @param catalog_persistentdocument_currency $document
 * @param Integer $parentNodeId Parent node ID where to save the document.
 * @return void
 */
//	protected function postInsert($document, $parentNodeId)
//	{
//	}


/**
 * @param catalog_persistentdocument_currency $document
 * @param Integer $parentNodeId Parent node ID where to save the document.
 * @return void
 */
//	protected function preUpdate($document, $parentNodeId)
//	{
//	}


/**
 * @param catalog_persistentdocument_currency $document
 * @param Integer $parentNodeId Parent node ID where to save the document.
 * @return void
 */
//	protected function postUpdate($document, $parentNodeId)
//	{
//	}


/**
 * @param catalog_persistentdocument_currency $document
 * @param Integer $parentNodeId Parent node ID where to save the document.
 * @return void
 */
//	protected function postSave($document, $parentNodeId)
//	{
//	}


/**
 * @param catalog_persistentdocument_currency $document
 * @return void
 */
//	protected function preDelete($document)
//	{
//	}


/**
 * @param catalog_persistentdocument_currency $document
 * @return void
 */
//	protected function preDeleteLocalized($document)
//	{
//	}


/**
 * @param catalog_persistentdocument_currency $document
 * @return void
 */
//	protected function postDelete($document)
//	{
//	}


/**
 * @param catalog_persistentdocument_currency $document
 * @return void
 */
//	protected function postDeleteLocalized($document)
//	{
//	}


/**
 * @param catalog_persistentdocument_currency $document
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
 * @param catalog_persistentdocument_currency $document
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
 * @param catalog_persistentdocument_currency $document
 * @param String $tag
 * @return void
 */
//	public function tagAdded($document, $tag)
//	{
//	}


/**
 * @param catalog_persistentdocument_currency $document
 * @param String $tag
 * @return void
 */
//	public function tagRemoved($document, $tag)
//	{
//	}


/**
 * @param catalog_persistentdocument_currency $fromDocument
 * @param f_persistentdocument_PersistentDocument $toDocument
 * @param String $tag
 * @return void
 */
//	public function tagMovedFrom($fromDocument, $toDocument, $tag)
//	{
//	}


/**
 * @param f_persistentdocument_PersistentDocument $fromDocument
 * @param catalog_persistentdocument_currency $toDocument
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
 * @param catalog_persistentdocument_currency $document
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
 * @param catalog_persistentdocument_currency $newDocument
 * @param catalog_persistentdocument_currency $originalDocument
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
 * @param catalog_persistentdocument_currency $newDocument
 * @param catalog_persistentdocument_currency $originalDocument
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
 * @param catalog_persistentdocument_currency $document
 * @param string $lang
 * @param array $parameters
 * @return string
 */
//	public function generateUrl($document, $lang, $parameters)
//	{
//	}


/**
 * Filter the parameters used to generate the document url.
 *
 * @param f_persistentdocument_PersistentDocument $document
 * @param string $lang
 * @param array $parameters may be an empty array
 */
//	public function filterDocumentUrlParams($document, $lang, $parameters)
//	{
//		return $parameters;
//	}


/**
 * @param catalog_persistentdocument_currency $document
 * @return integer | null
 */
//	public function getWebsiteId($document)
//	{
//	}


/**
 * @param catalog_persistentdocument_currency $document
 * @return website_persistentdocument_page | null
 */
//	public function getDisplayPage($document)
//	{
//	}


/**
 * @param catalog_persistentdocument_currency $document
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
 * @param catalog_persistentdocument_currency $document
 * @param string $bockName
 * @return array with entries 'module' and 'template'. 
 */
//	public function getSolrserachResultItemTemplate($document, $bockName)
//	{
//		return array('module' => 'catalog', 'template' => 'Catalog-Inc-CurrencyResultDetail');
//	}
}