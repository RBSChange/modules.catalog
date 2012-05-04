<?php
/**
 * catalog_AttributefolderService
 * @package catalog
 */
class catalog_AttributefolderService extends f_persistentdocument_DocumentService
{
	/**
	 *
	 * @var catalog_AttributefolderService
	 */
	private static $instance;
	
	/**
	 *
	 * @return catalog_AttributefolderService
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
	 *
	 * @return catalog_persistentdocument_attributefolder
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/attributefolder');
	}
	
	/**
	 * Create a query based on 'modules_catalog/attributefolder' model.
	 * Return document that are instance of modules_catalog/attributefolder,
	 * including potential children.
	 * 
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/attributefolder');
	}
	
	/**
	 * Create a query based on 'modules_catalog/attributefolder' model.
	 * Only documents that are strictly instance of
	 * modules_catalog/attributefolder
	 * (not children) will be retrieved
	 * 
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/attributefolder', false);
	}
	
	/**
	 * Cache for attributefolder
	 * 
	 * @var catalog_persistentdocument_attributefolder
	 */
	private $attributeFolder;
	
	/**
	 *
	 * @return catalog_persistentdocument_attributefolder
	 */
	public function getAttributeFolder()
	{
		if ($this->attributeFolder === null)
		{
			// There should be only one shopfolder.
			$this->attributeFolder = $this->createQuery()->findUnique();
		}
		return $this->attributeFolder;
	}
	
	protected function postSave($document, $parentNodeId)
	{
		$this->resetAttributeFolderCache();
	}
	
	protected function postDelete($document)
	{
		$this->resetAttributeFolderCache();
	}
	
	protected function resetAttributeFolderCache()
	{
		$this->attributeFolder = null;
	}
	
	/**
	 * @param string $code        	
	 * @return catalog_AttributeDefinition
	 * @throws Exception if attribute does not exist
	 */
	public function getAttributeInfo($code)
	{
		$attF = $this->getAttributeFolder();
		if ($attF)
		{
			$attr = $attF->getAttributeByCode($code);
			if ($attr)
			{
				return $attr;
			}
		}
		throw new Exception("Attribute $code does not exist");
	}
	
	/**
	 * @return catalog_AttributeDefinition[]
	 */
	public function getAttributesForProduct($product)
	{
		$attrFolder = $this->getAttributeFolder();
		return $attrFolder ? $attrFolder->getAttributes() : array();
	}
	
	/**
	 * @param catalog_persistentdocument_attributefolder $document
	 * @param string[] $propertiesName
	 * @param array $datas
	 * @param integer $parentId
	 */
	public function addFormProperties($document, $propertiesName, &$datas, $parentId = null)
	{
		if (in_array('attributesJSON', $propertiesName))
		{
			$datas['allowedlisttype'] = DocumentHelper::expandAllowAttribute('[modules_list_list]');
		}
	}
	
	/**
	 * @param catalog_persistentdocument_attributefolder $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$data = parent::getResume($document, $forModuleName, $allowedSections);
		$attrFolder = $this->getAttributeFolder();
		$attrs = $attrFolder ? $attrFolder->getAttributes() : array();
		$data['properties']['attributsCount'] = count($attrs);
		$data['properties']['i18npackage'] = array('label' =>'m.catalog.attributes');
		$package = i18n_PackageService::getInstance()->createQuery()->add(Restrictions::eq('label', 'm.catalog.attributes'))->findUnique();
		if ($package)
		{
			$uri = array('i18n', 'openDocument', $package->getPersistentModel()->getBackofficeName(), $package->getId(), 'resume');
			$data['properties']['i18npackage']['jsaction'] = "openActionUri('". implode(',', $uri)."')";
		}
		else
		{
			$data['properties']['i18npackage']['jsaction'] = "window.showModule('i18n')";
		}
		return $data;
	}	
	
	public function setDefaultI18nLabel($key, $text)
	{
		$tm = f_persistentdocument_TransactionManager::getInstance();
		try
		{
			$tm->beginTransaction();
			$ls = LocaleService::getInstance();
			$lcid = $ls->getLCID(RequestContext::getInstance()->getLang());
			list($keyPath, $id) = $ls->explodeKey($key);
			if (f_util_StringUtils::isEmpty($text) || $key == $text)
			{
				$text = $id;
			}
			$format = 'TEXT';
			LocaleService::getInstance()->updateUserEditedKey($lcid, $id, $keyPath, $text, $format);
			$tm->commit();
		} 
		catch (Exception $e) 
		{
			$tm->rollback($e);
			throw $e;
		}
	}
}