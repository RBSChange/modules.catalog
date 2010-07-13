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
}