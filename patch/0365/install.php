<?php
/**
 * catalog_patch_0365
 * @package modules.catalog
 */
class catalog_patch_0365 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeSQLQuery("UPDATE f_document SET document_model = 'modules_catalog/productqueryfolder' WHERE document_model = 'modules_featurepacka/productqueryfolder';");
		$this->executeSQLQuery("UPDATE m_generic_doc_folder SET document_model = 'modules_catalog/productqueryfolder' WHERE document_model = 'modules_featurepacka/productqueryfolder';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id1 = 'modules_catalog/productqueryfolder' WHERE document_model_id1 = 'modules_featurepacka/productqueryfolder';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id2 = 'modules_catalog/productqueryfolder' WHERE document_model_id2 = 'modules_featurepacka/productqueryfolder';");
		$this->executeSQLQuery("TRUNCATE f_cache;");
		$this->execChangeCommand("compile-editor-config"); // This is not always executed on compile-all...
	}
}