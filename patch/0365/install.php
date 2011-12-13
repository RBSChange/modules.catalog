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
		$this->log('Rename: modules_featurepacka/productqueryfolder');
		$this->renameModelName('modules_featurepacka/productqueryfolder', 'modules_catalog/productqueryfolder', 'm_generic_doc_folder');
				
		if (!is_readable(f_util_FileUtils::buildChangeBuildPath('modules', 'catalog', 'dataobject', 'm_catalog_doc_comparableproperty.mysql.sql')))
		{
			$this->log('Compile documents...');
			$this->execChangeCommand("compile-documents");
			
			$this->log('Generate database...');
			$this->execChangeCommand("generate-database");
			
		}
				
		try 
		{
			$this->log('Rename doc: modules_featurepackb/comparableproperty');
			
			$sql = "INSERT INTO `m_catalog_doc_comparableproperty`(`document_id`, `document_model`, `document_label`, `document_author`, `document_authorid`, `document_creationdate`, `document_modificationdate`, `document_publicationstatus`, `document_lang`, `document_modelversion`, `document_version`, `document_startpublicationdate`, `document_endpublicationdate`, `document_metas`, `classname`, `parametersserialized`)
			SELECT `document_id`, `document_model`, `document_label`, `document_author`, `document_authorid`, `document_creationdate`, `document_modificationdate`, `document_publicationstatus`, `document_lang`, `document_modelversion`, `document_version`, `document_startpublicationdate`, `document_endpublicationdate`, `document_metas`, `classname`, `parametersserialized` FROM `m_featurepackb_doc_comparableproperty`";
			$this->executeSQLQuery($sql);
			
			$this->renameModelName('modules_featurepackb/comparableproperty', 'modules_catalog/comparableproperty', 'm_catalog_doc_comparableproperty');
			
			$this->log('Rename doc: modules_featurepackb/comparablepropertyfolder');
			$this->renameModelName('modules_featurepackb/comparablepropertyfolder', 'modules_catalog/comparablepropertyfolder', 'm_generic_doc_folder');
			
			$sql = "RENAME TABLE `m_featurepackb_doc_comparableproperty` TO `m_featurepackb_old_comparableproperty`";
			$this->executeSQLQuery($sql);
			
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			$this->Log('No migration needed');
		}
		
		$props = array('featurepackb_GetterComparableProperty' => 'catalog_GetterComparableProperty',
				'featurepackb_PersistentComparableProperty' => 'catalog_PersistentComparableProperty',
				'featurepackb_PriceComparableProperty' => 'catalog_PriceComparableProperty',
				'featurepackb_ReviewComparableProperty' => 'catalog_ReviewComparableProperty',
				'featurepackb_ShortDescriptionComparableProperty' => 'catalog_ShortDescriptionComparableProperty');
		
		$this->beginTransaction();
		foreach ($props as $oldname => $newName)
		{
			$sql = "UPDATE `m_catalog_doc_comparableproperty` SET  `classname`= '".$newName."' WHERE  `classname`= '".$oldname."'";
			$this->executeSQLQuery($sql);
		}
		$this->commit();
		

		$edtPath = f_util_FileUtils::buildOverridePath('modules', 'catalog', 'forms', 'editor', 'comparableproperty');
		if (is_dir($edtPath))
		{
			$this->log('remove dir: ' . $edtPath);
			f_util_FileUtils::rmdir($edtPath);
		}
		
		$edtPath = f_util_FileUtils::buildOverridePath('modules', 'catalog', 'forms', 'editor', 'comparablepropertyfolder');
		if (is_dir($edtPath))
		{
			$this->log('remove dir: ' . $edtPath);
			f_util_FileUtils::rmdir($edtPath);
		}

		$this->log('Rename block: modules_featurepackb_ProductComparison');
		$this->renameBlock('modules_featurepackb_ProductComparison', 'modules_catalog_ProductComparison');
		
		$this->log('Rename block: modules_featurepackb_ProductComparison');
		$this->renameBlock('modules_featurepackb_ShippingModeConfigurationContainer', 'modules_order_ShippingModeConfigurationContainer');		
					
		$this->log('Rename tag: functional_featurepackb_productlist-comparison');
		$this->renameTag('functional_featurepackb_productlist-comparison', 'functional_catalog_productlist-comparison');
		
		
		$this->removeImport('actions.xml');
		$this->removeImport('perspective.xml');
		$this->removeImport('rights.xml');
		
		//override/modules/catalog/templates
				
		$path = f_util_FileUtils::buildWebeditPath('override', 'modules', 'catalog', 'templates');
		if (is_dir($path))
		{
			$files = scandir($path);
			foreach ($files as $fileName) 
			{
				$fullPath = $path . DIRECTORY_SEPARATOR . $fileName;
				if ($fileName[0] === '.' || is_dir($fullPath)) {continue;}
				if (is_readable($fullPath))
				{
					$content = file_get_contents($fullPath);
					$newContent = str_replace('featurepackb', 'catalog', $content);
					if ($content !== $newContent)
					{
						$this->log('Patch template: ' . $fullPath);
						file_put_contents($fullPath, $newContent);
					}
				}
			}		
		}
		
		$bp = f_util_FileUtils::buildChangeBuildPath('modules', 'featurepacka');
		if (is_dir($bp))
		{
			$this->log('Clean dir: ' . $bp);
			f_util_FileUtils::rmdir($bp);
		}
		
		$bp = f_util_FileUtils::buildChangeBuildPath('modules', 'featurepackb');
		if (is_dir($bp))
		{
			$this->log('Clean dir: ' . $bp);
			f_util_FileUtils::rmdir($bp);
		}

		$this->log('Compile editors config...');
		$this->execChangeCommand("compile-editors-config"); // This is not always executed on compile-all...
	}
	
	private function removeImport($fileName)
	{
		$path = f_util_FileUtils::buildWebeditPath('override', 'modules', 'catalog', 'config', $fileName);
		if (is_readable($path))
		{
			$doc = f_util_DOMUtils::fromPath($path);
			
			//<import modulename="featurepackb" configfilename="catalog.actions"/>
			$node = $doc->findUnique("//import[@modulename='featurepackb']");
			if ($node !== null)
			{
				$node->parentNode->removeChild($node);
				$this->log('Update conf:' . $path);
				$doc->save($path);
			}	
		}
	}
	
	private function renameModelName($oldName, $newName, $dbTableName)
	{
		$this->beginTransaction();
		$this->executeSQLQuery("UPDATE f_document SET document_model = '".$newName."' WHERE document_model = '".$oldName."'");
		$this->executeSQLQuery("UPDATE ".$dbTableName." SET document_model = '".$newName."' WHERE document_model = '".$oldName."'");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id1 = '".$newName."' WHERE document_model_id1 = '".$oldName."'");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id2 = '".$newName."' WHERE document_model_id2 = '".$oldName."'");
		$this->commit();
	}
	
	private function renameBlock($oldName, $newName)
	{
		$select = "SELECT `document_id`, `lang_i18n` FROM `m_website_doc_page_i18n` WHERE `content_i18n` LIKE '%".$oldName."%'";
		$stmt = $this->executeSQLSelect($select);
		$chunks = array_chunk($stmt->fetchAll(PDO::FETCH_NUM), 100);
		$pdo = $this->getPersistentProvider()->getDriver();
		
		foreach ($chunks as $chunk) 
		{
			try 
			{		
				
				$this->beginTransaction();			
				foreach ($chunk as $row)
				{
					list ($id, $lang) = $row;
					$select = "SELECT `content_i18n` FROM `m_website_doc_page_i18n` WHERE `document_id` = " .$id . " AND `lang_i18n` = '" . $lang . "'";
					$rows = $this->executeSQLSelect($select)->fetchAll(PDO::FETCH_NUM);		
					if (count($rows))
					{
						$content = $rows[0][0];		
						$this->log('Patch page -> ' . $id . '/' .  $lang);		
						$content = $pdo->quote(str_replace($oldName, $newName, $content));
		
						$sql = "UPDATE `m_website_doc_page_i18n` SET `content_i18n` = " .$content . " WHERE `document_id` = " .$id . " AND `lang_i18n` = '" . $lang . "'";
						$this->executeSQLSelect($sql);
						
						$sql = "UPDATE `m_website_doc_page` SET `content` = " .$content . " WHERE `document_id` = " . $id . " AND `document_lang` = '" . $lang . "'";
						$this->executeSQLSelect($sql);
					}	
				}
				$this->commit();			
			}
			catch (Exception $e)
			{
				Framework::exception($e);
				$this->rollBack($e);
			}
		}
	}
	
	private function renameTag($oldName, $newName)
	{
		$pdo = $this->getPersistentProvider()->getDriver();
		$this->beginTransaction();
		$query = "SELECT `document_id` , `document_metas` FROM `m_website_doc_page` INNER JOIN f_tags ON `id` = `document_id` WHERE tag = '" . $oldName . "'";
		$stmt = $this->executeSQLSelect($query);
		foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row) 
		{
			list($id, $metasStr) = $row;
			$this->log('Update page: ' . $id);
			$sql = "UPDATE `f_tags` SET `tag`= '".$newName."' WHERE `id` = ".$id;
			$this->executeSQLQuery($sql);
			
			if (!empty($metasStr))
			{
				$meta = unserialize($metasStr);
				if (isset($meta['f_tags']) && is_array($meta['f_tags']))
				{
					foreach ($meta['f_tags'] as $i => $tag) 
					{
						if ($tag == $oldName)
						{
							$meta['f_tags'][$i] = $newName;
						}
					}
					
					$metasStr = $pdo->quote(serialize($meta));
					$this->log('Update page tag metas: ' . $metasStr);
					$sql = "UPDATE `m_website_doc_page` SET `document_metas`= ".$metasStr." WHERE `document_id` = ".$id;
					$this->executeSQLQuery($sql);
				}
			}
		}
		$this->commit();
	}
	
	
}