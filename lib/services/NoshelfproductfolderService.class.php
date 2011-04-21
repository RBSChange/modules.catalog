<?php
/**
 * catalog_NoshelfproductfolderService
 * @package modules.catalog
 */
class catalog_NoshelfproductfolderService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_NoshelfproductfolderService
	 */
	private static $instance;

	/**
	 * @return catalog_NoshelfproductfolderService
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
	 * @return catalog_persistentdocument_noshelfproductfolder
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/noshelfproductfolder');
	}

	/**
	 * Create a query based on 'modules_catalog/noshelfproductfolder' model.
	 * Return document that are instance of modules_catalog/noshelfproductfolder,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/noshelfproductfolder');
	}
	
	/**
	 * Create a query based on 'modules_catalog/noshelfproductfolder' model.
	 * Only documents that are strictly instance of modules_catalog/noshelfproductfolder
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/noshelfproductfolder', false);
	}
	
	public function getVirtualChildrenAt($document, $subModelNames, $locateDocumentId, $pageSize, &$startIndex, &$totalCount)
	{
		$totalCount = f_util_ArrayUtils::firstElement(catalog_ProductService::getInstance()->createQuery()
			->add(Restrictions::isEmpty("shelf"))
			->setProjection(Projections::rowCount("c"))
			->findColumn("c"));
		$products = catalog_ProductService::getInstance()->createQuery()
			->add(Restrictions::ne("model", "modules_catalog/productdeclination"))
			->add(Restrictions::isEmpty("shelf"))
			->setMaxResults($pageSize)
			->setFirstResult($startIndex)
			->addOrder(Order::asc("label"))
			->find();
		return $products;
	}
	
	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
//	protected function preSave($document, $parentNodeId)
//	{
//
//	}

	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preInsert($document, $parentNodeId)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postInsert($document, $parentNodeId)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preUpdate($document, $parentNodeId)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postUpdate($document, $parentNodeId)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postSave($document, $parentNodeId)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @return void
	 */
//	protected function preDelete($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @return void
	 */
//	protected function preDeleteLocalized($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @return void
	 */
//	protected function postDelete($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @return void
	 */
//	protected function postDeleteLocalized($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $document
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
	 * @param catalog_persistentdocument_noshelfproductfolder $document
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
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagAdded($document, $tag)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagRemoved($document, $tag)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $fromDocument
	 * @param f_persistentdocument_PersistentDocument $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedFrom($fromDocument, $toDocument, $tag)
//	{
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $fromDocument
	 * @param catalog_persistentdocument_noshelfproductfolder $toDocument
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
	 * @param catalog_persistentdocument_noshelfproductfolder $document
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
	 * @param catalog_persistentdocument_noshelfproductfolder $newDocument
	 * @param catalog_persistentdocument_noshelfproductfolder $originalDocument
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
	 * @param catalog_persistentdocument_noshelfproductfolder $newDocument
	 * @param catalog_persistentdocument_noshelfproductfolder $originalDocument
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
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @param string $lang
	 * @param array $parameters
	 * @return string
	 */
//	public function generateUrl($document, $lang, $parameters)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @return integer | null
	 */
//	public function getWebsiteId($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @return website_persistentdocument_page | null
	 */
//	public function getDisplayPage($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_noshelfproductfolder $document
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
	 * @param catalog_persistentdocument_noshelfproductfolder $document
	 * @param string $bockName
	 * @return array with entries 'module' and 'template'. 
	 */
//	public function getSolrserachResultItemTemplate($document, $bockName)
//	{
//		return array('module' => 'catalog', 'template' => 'Catalog-Inc-NoshelfproductfolderResultDetail');
//	}
}