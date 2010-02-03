<?php
/**
 * catalog_patch_0302
 * @package modules.catalog
 */
class catalog_patch_0302 extends patch_BasePatch
{ 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		parent::execute();
		
		$this->executeSQLQuery("CREATE TABLE IF NOT EXISTS `m_catalog_doc_attributefolder` (
	`document_id` int(11) NOT NULL default '0',
	`document_model` varchar(50) NOT NULL default '',
	  `document_author` varchar(50),
	  `document_authorid` int(11),
	  `document_creationdate` datetime,
	  `document_modificationdate` datetime,
	  `document_publicationstatus` ENUM('DRAFT', 'CORRECTION', 'ACTIVE', 'PUBLICATED', 'DEACTIVATED', 'FILED', 'DEPRECATED', 'TRASH', 'WORKFLOW') NULL DEFAULT NULL,
	  `document_lang` varchar(2),
	  `document_modelversion` varchar(20),
	  `document_version` int(11),
	  `document_startpublicationdate` datetime,
	  `document_endpublicationdate` datetime,
	  `document_metas` text,
	  `document_label` varchar(255),
	  `serializedattributes` mediumtext,
PRIMARY KEY  (`document_id`)
) TYPE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin;");
		
		$folder = catalog_AttributefolderService::getInstance()->getAttributeFolder();
		if ($folder === null)
		{
			$folder = catalog_AttributefolderService::getInstance()->getNewDocumentInstance();
			$folder->save();
			$rootid = ModuleService::getInstance()->getRootFolderId('catalog');			
			TreeService::getInstance()->newFirstChild($rootid, $folder->getId());
			echo "Attribut folder added\n";
		}
		
		$this->executeSQLQuery("ALTER TABLE `m_catalog_doc_product` ADD `serializedattributes` mediumtext");
	}

	/**
	 * Returns the name of the module the patch belongs to.
	 *
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'catalog';
	}

	/**
	 * Returns the number of the current patch.
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0302';
	}
}