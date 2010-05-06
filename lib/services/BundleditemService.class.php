<?php
/**
 * catalog_BundleditemService
 * @package modules.catalog
 */
class catalog_BundleditemService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_BundleditemService
	 */
	private static $instance;

	/**
	 * @return catalog_BundleditemService
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
	 * @return catalog_persistentdocument_bundleditem
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/bundleditem');
	}

	/**
	 * Create a query based on 'modules_catalog/bundleditem' model.
	 * Return document that are instance of modules_catalog/bundleditem,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/bundleditem');
	}
	
	/**
	 * Create a query based on 'modules_catalog/bundleditem' model.
	 * Only documents that are strictly instance of modules_catalog/bundleditem
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/bundleditem', false);
	}
	
	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param string $lang
	 * @param array $parameters
	 * @return string
	 */
	public function generateUrl($document, $lang, $parameters)
	{
		$bundleProducts = $document->getBundleproductArrayInverse();
		if (count($bundleProducts) == 1)
		{
			if (!is_array($parameters)) {$parameters = array();}
			
			if (!isset($parameters['catalogParam']))
			{
				$parameters['catalogParam'] = array('bundleditemid' => $document->getId());
			} 
			else
			{
				$parameters['catalogParam']['bundleditemid']  = $document->getId();
			}
			return LinkHelper::getDocumentUrl($bundleProducts[0], $lang, $parameters);
		}
		return null;
	}
	
	
	
	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
//	protected function preSave($document, $parentNodeId = null)
//	{
//
//	}


	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preInsert($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postInsert($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preUpdate($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postUpdate($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postSave($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @return void
	 */
//	protected function preDelete($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @return void
	 */
//	protected function preDeleteLocalized($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @return void
	 */
//	protected function postDelete($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @return void
	 */
//	protected function postDeleteLocalized($document)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_bundleditem $document
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
	 * @param catalog_persistentdocument_bundleditem $document
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
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagAdded($document, $tag)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagRemoved($document, $tag)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_bundleditem $fromDocument
	 * @param f_persistentdocument_PersistentDocument $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedFrom($fromDocument, $toDocument, $tag)
//	{
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $fromDocument
	 * @param catalog_persistentdocument_bundleditem $toDocument
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
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param Integer $destId
	 * @return void
	 */
//	protected function onDocumentMoved($document, $destId)
//	{
//	}

	/**
	 * this method is call before save the duplicate document.
	 * If this method not override in the document service, the document isn't duplicable.
	 * An IllegalOperationException is so launched.
	 *
	 * @param f_persistentdocument_PersistentDocument $newDocument
	 * @param f_persistentdocument_PersistentDocument $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function preDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//		throw new IllegalOperationException('This document cannot be duplicated.');
//	}

	/**
	 * Returns the URL of the document if has no URL Rewriting rule.
	 *
	 * @param catalog_persistentdocument_bundleditem $document
	 * @param string $lang
	 * @param array $parameters
	 * @return string
	 */
//	public function generateUrl($document, $lang, $parameters)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_bundleditem $document
	 * @return integer | null
	 */
//	public function getWebsiteId($document)
//	{
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return website_persistentdocument_page | null
	 */
//	public function getDisplayPage($document)
//	{
//	}
}