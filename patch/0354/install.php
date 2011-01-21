<?php
/**
 * catalog_patch_0354
 * @package modules.catalog
 */
class catalog_patch_0354 extends patch_BasePatch
{

	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->log('compile-documents ...');
		$this->execChangeCommand('compile-documents');
		
		$this->log('generate-database ...');
		$this->execChangeCommand('generate-database');
				
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		
		$sql ="SELECT `relation_id` FROM `f_relationname` WHERE `property_name` = 'declinedproduct'";
		$stmt = $pp->getDriver()->prepare($sql);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
		if (is_array($rows) && count($rows) == 1)
		{
			$relationID = $rows[0];
		}
		else
		{
			$this->logError('Unable to find declinedproduct relation id');
			die();
		}
		
		$sql ="SELECT `relation_id` FROM `f_relationname` WHERE `property_name` = 'declination'";
		$stmt = $pp->getDriver()->prepare($sql);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
		if (is_array($rows) && count($rows) == 1)
		{
			$oldRelationID = $rows[0];
		}
		else
		{
			$this->logError('Unable to find old declination relation id');
			die();
		}
			
		try 
		{
			$this->beginTransaction();
			
			$model = f_persistentdocument_PersistentDocumentModel::getInstance('catalog', 'declinedproduct');
			$modelNames = array($model->getName());
			if (is_array($model->getChildrenNames()))
			{
				$modelNames = array_merge($modelNames, $model->getChildrenNames());
			}
			$sqlIn = "'" . implode("', '", $modelNames) . "'";
			
			$model = f_persistentdocument_PersistentDocumentModel::getInstance('catalog', 'productdeclination');
			$declinationModelNames = array($model->getName());
			if (is_array($model->getChildrenNames()))
			{
				$declinationModelNames = array_merge($declinationModelNames, $model->getChildrenNames());
			}
			$sqlDeclinationIn = "'" . implode("', '", $declinationModelNames) . "'";

			$this->log("Deplacement des $sqlDeclinationIn dans la table m_catalog_doc_declinedproduct");
			
			$sql = "INSERT INTO `m_catalog_doc_declinedproduct` (`document_id`, `document_model`, `document_label`, `document_author`, 
				`document_authorid`, `document_creationdate`, `document_modificationdate`, `document_lang`, 
				`document_modelversion`, `document_version`, `document_startpublicationdate`, `document_endpublicationdate`, 
				`document_metas`, `codereference`, `document_publicationstatus`, `description`, `visual`, 
				`shelf`, `additionnalvisual`, `pictogram`, `brand`, `complementary`, `similar`, 
				`upsell`, `serializedattributes`, `shippingmodeid`, `pagetitle`, `pagedescription`, 
				`pagekeywords`, `synchronizeprices`, `showaxeinlist`, `axe1`) 
				SELECT `document_id`, `document_model`, `document_label`, `document_author`, 
				`document_authorid`, `document_creationdate`, `document_modificationdate`, `document_lang`, 
				`document_modelversion`, `document_version`, `document_startpublicationdate`, `document_endpublicationdate`, 
				`document_metas`, `codereference`, `document_publicationstatus`, `description`, `visual`, 
				`shelf`, `additionnalvisual`, `pictogram`, `brand`, `complementary`, `similar`, 
				`upsell`, `serializedattributes`, `shippingmodeid`, `pagetitle`, `pagedescription`, 
				`pagekeywords`, `synchronizeprices`, 0 , 'property::label' FROM `m_catalog_doc_product` WHERE `document_model` IN ('modules_catalog/declinedproduct')";
			
			$sql = str_replace("'modules_catalog/declinedproduct'", $sqlIn, $sql);
			$this->executeSQLQueryAAA($sql);
			
			$sql = "DELETE FROM m_catalog_doc_product WHERE `document_model` IN ('modules_catalog/declinedproduct')";
			$sql = str_replace("'modules_catalog/declinedproduct'", $sqlIn, $sql);
			$this->executeSQLQueryAAA($sql);
			
			$this->log("Deplacement des localisation $sqlDeclinationIn dans la table m_catalog_doc_declinedproduct_i18n");
			
			$sql ="INSERT INTO `m_catalog_doc_declinedproduct_i18n` (`document_id`, `lang_i18n`, `document_label_i18n`, 
				`document_publicationstatus_i18n`, `description_i18n`, `pagetitle_i18n`, `pagedescription_i18n`, `pagekeywords_i18n`) 
				SELECT `m_catalog_doc_product_i18n`.`document_id`, `lang_i18n`, `document_label_i18n`, 
				`document_publicationstatus_i18n`, `description_i18n`, `pagetitle_i18n`, `pagedescription_i18n`, `pagekeywords_i18n` 
				FROM `m_catalog_doc_product_i18n` INNER JOIN `m_catalog_doc_declinedproduct` WHERE `m_catalog_doc_declinedproduct`.`document_id` = `m_catalog_doc_product_i18n`.`document_id`";
			$this->executeSQLQueryAAA($sql);

			$sql = "DELETE FROM m_catalog_doc_product_i18n WHERE `document_id` NOT IN (SELECT document_id FROM m_catalog_doc_product)";
			$this->executeSQLQueryAAA($sql);
			
			
			$this->log("Transformation de la relation declination($oldRelationID) en declinedproduct($relationID)");
			
			$sql = "INSERT INTO `f_relation` (`relation_id1`, `relation_id2`, `relation_order`, `relation_name`, `document_model_id1`, `document_model_id2`, `relation_id`) 
			SELECT `relation_id2`, `relation_id1`,  0, 'declinedproduct', `document_model_id2`, `document_model_id1`, $relationID FROM `f_relation` WHERE `relation_id` = $oldRelationID and relation_id1 in (select document_id from m_catalog_doc_declinedproduct)";
			$stmt = $this->executeSQLQueryAAA($sql);
			
			$this->log("Mise à jour de la relation declinedproduct($relationID) dans la table m_catalog_doc_product");
			
			$sql = "UPDATE `m_catalog_doc_product` SET `compiled` = 0, `declinedproduct` = (SELECT `relation_id1` FROM `f_relation` WHERE `relation_id` = $oldRelationID AND `m_catalog_doc_product`.`document_id` = `relation_id2`) WHERE document_model IN ('modules_catalog/productdeclination')";
			$sql = str_replace("'modules_catalog/productdeclination'", $sqlDeclinationIn, $sql);
			$stmt = $this->executeSQLQueryAAA($sql);
			
			$sql = "UPDATE `m_catalog_doc_product` SET `indexindeclinedproduct` = (SELECT `relation_order` FROM `f_relation` WHERE `relation_id` = $oldRelationID AND `m_catalog_doc_product`.`document_id` = `relation_id2`) WHERE document_model IN ('modules_catalog/productdeclination')";
			$sql = str_replace("'modules_catalog/productdeclination'", $sqlDeclinationIn, $sql);
			$stmt = $this->executeSQLQueryAAA($sql);			
			
			$sql = "DELETE FROM `f_relation` WHERE `relation_id` = $oldRelationID and relation_id1 in (select document_id from m_catalog_doc_declinedproduct)";
			$this->executeSQLQueryAAA($sql);
			
			
			$this->log("Suppession des produit compilé associé au document $sqlIn");
			$sql = "DELETE FROM f_document WHERE document_id in(SELECT document_id FROM m_catalog_doc_compiledproduct where product in(SELECT document_id FROM m_catalog_doc_declinedproduct))";
			$this->executeSQLQueryAAA($sql);
			
			$sql = "DELETE FROM m_catalog_doc_compiledproduct where product in (SELECT document_id FROM m_catalog_doc_declinedproduct)";
			$this->executeSQLQueryAAA($sql);
			
			$sql = "DELETE FROM `f_relation` WHERE `relation_id2` IN (SELECT document_id FROM m_catalog_doc_declinedproduct) AND document_model_id1 = 'modules_catalog/compiledproduct'";
			$this->executeSQLQueryAAA($sql);
			
			$this->commit();			
		}
		catch (Exception $e)
		{
			$this->rollBack($e);
			$this->logError('Unable to update database');
			die();
		}
		
		$this->log("Ajout du champ showInList au document compiledproduct ...");
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/compiledproduct.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'compiledproduct');
		$newProp = $newModel->getPropertyByName('showInList');
		$this->getPersistentProvider()->addProperty('catalog', 'compiledproduct', $newProp);
		
		$this->log("Ajout du champ publicationCode au document compiledproduct ...");
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/compiledproduct.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'compiledproduct');
		$newProp = $newModel->getPropertyByName('publicationCode');
		$this->getPersistentProvider()->addProperty('catalog', 'compiledproduct', $newProp);

		
		$sql = "UPDATE `m_catalog_doc_compiledproduct` SET `showInList` =  1, publicationCode = 0";
		$this->executeSQLQueryAAA($sql);
		
		$this->log("Ajout du champ declinable au document kititem ...");
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/kititem.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'kititem');
		$newProp = $newModel->getPropertyByName('declinable');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'kititem', $newProp);		
		
		$this->log("Migration des produits decliné dans les kititem ...");
		try 
		{
			$this->beginTransaction();
			$sql = "SELECT `document_id` FROM `m_catalog_doc_kititem` WHERE `product` IN (SELECT `document_id` FROM `m_catalog_doc_declinedproduct`)";
			$stmt = $pp->getDriver()->prepare($sql);
			$stmt->execute();
			foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) 
			{
				$kitItem = catalog_persistentdocument_kititem::getInstanceById($row['document_id']);
				$declinedProduct = $kitItem->getProduct();
				if ($declinedProduct instanceof catalog_persistentdocument_declinedproduct) 
				{
					$declination = $declinedProduct->getFirstDeclination();
					if ($declination !== null)
					{
						$kitItem->setProduct($declination);
						$kitItem->setDeclinable(true);
						$kitItem->save();
					}
					else
					{
						$kitItem->delete();
					}
				}
			}
			$this->commit();			
		}
		catch (Exception $e)
		{
			$this->rollBack($e);
			$this->logError($e->getMessage());
			die();
		}		
		$this->execChangeCommand('compile-locales', array('catalog'));
		$this->executeModuleScript('axes.xml', 'catalog');
	}

	function executeSQLQueryAAA($sql)
	{
		$this->executeSQLQuery($sql);
	}
	
	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'catalog';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0354';
	}
}