<?php
/**
 * catalog_TopshelfService
 * @package catalog
 */
class catalog_TopshelfService extends catalog_ShelfService
{
	/**
	 * @var catalog_TopshelfService
	 */
	private static $instance;

	/**
	 * @return catalog_TopshelfService
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
	 * @return catalog_persistentdocument_topshelf
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/topshelf');
	}

	/**
	 * Create a query based on 'modules_catalog/topshelf' model.
	 * Return document that are instance of modules_catalog/topshelf,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/topshelf');
	}
	
	/**
	 * Create a query based on 'modules_catalog/topshelf' model.
	 * Only documents that are strictly instance of modules_catalog/topshelf
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/topshelf', false);
	}
	
	/**
	 * Called before the moveToOperation starts. The method is executed INSIDE a
	 * transaction.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $destId
	 */
	protected function onMoveToStart($document, $destId)
	{
		// Nothing to do: top shelves may only be re-ordered and it doesn't affect related systemtopics.
	}

	/**
	 * @param catalog_persistentdocument_topshelf $document
	 * @param Integer $destId
	 * @return void
	 */
	protected function onDocumentMoved($document, $destId)
	{
		// Nothing to do: top shelves may only be re-ordered and it doesn't affect related compiledproducts.
	}
	
	/**
	 * @see f_persistentdocument_DocumentService::preDelete()
	 *
	 * @param catalog_persistentdocument_topshelf $document
	 */
	protected function preDelete($document)
	{
		// We need to remove ourself from the different shops...this will trigger the deletion of all the topics attached to the subshelf.
		$shops = $document->getShopArrayInverse();
		foreach ($shops as $shop)
		{
			$shop->removeTopShelf($document);
			$shop->save();
		}
	}
}