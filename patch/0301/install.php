<?php
/**
 * catalog_patch_0301
 * @package modules.catalog
 */
class catalog_patch_0301 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		parent::execute();
		
		$ts = TreeService::getInstance();
		$as = synchro_ArticleService::getInstance();
		$ps = catalog_ProductService::getInstance();
		$ss = catalog_ShelfService::getInstance();
		$tss = catalog_TopshelfService::getInstance();
		$shs = catalog_ShopService::getInstance();
		$sts = website_SystemtopicService::getInstance();
		$cps = catalog_CompiledproductService::getInstance();
		$ds = f_persistentdocument_DocumentService::getInstance();
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$tm = f_persistentdocument_TransactionManager::getInstance();
		$rootFolderId = ModuleService::getInstance()->getRootFolderId('catalog');
		
		$cps->disableCompilation();
		
		// Recompile listeners.
		$this->log('Recompile listeners...');
		ChangeProject::getInstance()->executeTask('compile-listeners');

		// update list
		$this->executeLocalXmlScript('list.xml');
		
		// Delete the old stock notification.
		$notification = notification_NotificationService::getInstance()->getNotificationByCodeName('modules_catalog/outofstock_notification');
		if ($notification !== null)
		{
			$notification->delete();
		}
				
		// Create the new one.
		$this->executeLocalXmlScript('notification.xml');
		
		// Add table for compiled-products.
		$this->log('Add table for compiled-products...');
		$this->executeSQLQuery("CREATE TABLE IF NOT EXISTS `m_catalog_doc_compiledproduct` (
				`document_id` int(11) NOT NULL default '0',
				`document_model` varchar(50) NOT NULL default '',
				`document_label` varchar(255),
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
				`product` int(11) default NULL,
				`topicid` int(11),
				`shelfid` int(11),
				`shopid` int(11),
				`websiteid` int(11),
				`shelfindex` int(11),
				`position` int(11),
				`price` float,
				`ratingaverage` float,
				`brandid` int(11),
				`brandlabel` varchar(255),
				`isdiscount` tinyint(1) NOT NULL default '0',
				`isavailable` tinyint(1) NOT NULL default '0',
				PRIMARY KEY  (`document_id`) 
			) TYPE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin;"
		);
		
		// Remove toics from catalog rootfolder.
		$this->log('Remove topics from catalog rootfolder...');
		$rootFolder = DocumentHelper::getDocumentInstance($rootFolderId);
		$rootFolder->removeAllTopics();
		$rootFolder->save();
		
		// Convert catalogs into shops.
		$this->log('Convert catalogs into shops...');
		$this->executeSQLQuery("RENAME TABLE m_catalog_doc_catalog TO m_catalog_doc_shop;");
		$this->executeSQLQuery("RENAME TABLE m_catalog_doc_catalog_i18n TO m_catalog_doc_shop_i18n;");
		$this->executeSQLQuery("UPDATE m_catalog_doc_shop SET document_model = 'modules_catalog/shop' WHERE document_model = 'modules_catalog/catalog';");
		$this->executeSQLQuery("UPDATE f_document SET document_model = 'modules_catalog/shop' WHERE document_model = 'modules_catalog/catalog';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id1 = 'modules_catalog/shop' WHERE document_model_id1 = 'modules_catalog/catalog';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id2 = 'modules_catalog/shop' WHERE document_model_id2 = 'modules_catalog/catalog';");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_shop DROP COLUMN isdefaultcatalog;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_shop CHANGE COLUMN displayprice displaypricewithtax tinyint(1) NOT NULL default '0';");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_shop ADD `topshelf` int(11) default '0';");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_shop ADD `displayoutofstock` tinyint(1) NOT NULL default '0';");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_shop ADD `alloworderoutofstock` tinyint(1) NOT NULL default '0';");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_shop ADD `pricemode` varchar(255);");
		
		// Add referencing fields to shops.
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/shop.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'shop');
		$newProp = $newModel->getPropertyByName('pageTitle');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shop', $newProp);
		$newProp = $newModel->getPropertyByName('pageDescription');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shop', $newProp);
		$newProp = $newModel->getPropertyByName('pageKeywords');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shop', $newProp);

		// Add the shopfolder.
		$shopfolder = catalog_ShopfolderService::getInstance()->getNewDocumentInstance();
		$shopfolder->save($rootFolderId);
		
		// Load preferences data and drop deprecated columns.
		$statement = $pp->executeSQLSelect('SELECT * FROM `m_catalog_doc_preferences`;');
		$statement->execute();
		$preferencesData = f_util_ArrayUtils::firstelement($statement->fetchAll());
		$displayoutofstock = ($preferencesData !== null && $preferencesData['displayarticlesoutofstock'] == 1) ? true : false;
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_preferences DROP COLUMN displayarticlesoutofstock;");
		$alloworderoutofstock = ($preferencesData !== null && $preferencesData['alloworderarticlesoutofstock'] == 1) ? true : false;
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_preferences DROP COLUMN alloworderarticlesoutofstock;");

		// Rename outOfStockXXX to stockAlertXXX fields in preferences.
		$archivePath = f_util_FileUtils::buildWebeditPath('modules/catalog/patch/'.$this->getNumber().'/preferences-old.xml');
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/preferences.xml');
		
		$oldModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($archivePath), 'catalog', 'preferences');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'preferences');
		
		$oldProp = $oldModel->getPropertyByName('outOfStockThreshold');		
		$newProp = $newModel->getPropertyByName('stockAlertThreshold');
		$pp->renameProperty('catalog', 'preferences', $oldProp, $newProp);
		
		$oldProp = $oldModel->getPropertyByName('outOfStockNotificationUser');
		$newProp = $newModel->getPropertyByName('stockAlertNotificationUser');
		$pp->renameProperty('catalog', 'preferences', $oldProp, $newProp);		
		
		// Update preferences.
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/preferences.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'preferences');
		$newProp = $newModel->getPropertyByName('synchronizeSimilar');
		$pp->addProperty('catalog', 'preferences', $newProp);
		$newProp = $newModel->getPropertyByName('synchronizeComplementary');
		$pp->addProperty('catalog', 'preferences', $newProp);
		$newProp = $newModel->getPropertyByName('suggestComplementaryFeederClass');
		$pp->addProperty('catalog', 'preferences', $newProp);
		$newProp = $newModel->getPropertyByName('suggestSimilarFeederClass');
		$pp->addProperty('catalog', 'preferences', $newProp);
		$newProp = $newModel->getPropertyByName('suggestUpsellFeederClass');
		$pp->addProperty('catalog', 'preferences', $newProp);

		$preferences = ModuleService::getInstance()->getPreferencesDocument('catalog');
		if ($preferences === null)
		{
			$this->executeLocalXmlScript('preferences.xml');
			$preferences = ModuleService::getInstance()->getPreferencesDocument('catalog');
		}
		$preferences->setSynchronizeSimilar(true);
		$preferences->setSynchronizeComplementary(true);
		$preferences->setSuggestComplementaryFeederClass('none');
		$preferences->setSuggestSimilarFeederClass('none');
		$preferences->setSuggestUpsellFeederClass('none');
		$preferences->save();				
		
		// Attach all shops to shopfolder.
		$shops = catalog_ShopService::getInstance()->createQuery()->find();
		$this->log('Update shops and attach them to shopfolder... (' . count($shops) . ' shops)');
		$shopfolder = catalog_ShopfolderService::getInstance()->createQuery()->findUnique();
		$parentNode = $ts->getInstanceByDocument($shopfolder);		
		foreach ($shops as $shop)
		{
			// Set as shopfolder child.
			$oldNode = $ts->getInstanceByDocument($shop);
			if ($oldNode !== null)
			{
				$ts->deleteNode($oldNode);
			}
			$ts->newLastChildForNode($parentNode, $shop->getId());
		}
		
		// Update shelves structure.
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_shelf DROP COLUMN catalog;");
		
		// Mutate all topics related to shelves and shops to system topics.
		$shops = $shs->createQuery()->find();
		$this->log('Mutate topics related shops... (' . count($shops) . ' shops)');
		foreach ($shops as $shop)
		{
			$systemTopic = $pp->mutate($shop->getTopic(), $sts->getNewDocumentInstance());
			$systemTopic->setReferenceId($shop->getId());
			$systemTopic->save();
		}
		$shelves = $ss->createQuery()->find();
		$this->log('Mutate topics related shelves... (' . count($shelves) . ' shelves)');
		foreach ($shelves as $shelf)
		{
			foreach ($shelf->getTopicArray() as $topic)
			{
				$systemTopic = $pp->mutate($topic, $sts->getNewDocumentInstance());
				$systemTopic->setReferenceId($shelf->getId());
				$systemTopic->save();
			}
		}
		$pp->reset();

		// update shop properties
		$defaultPriceMode = catalog_PriceHelper::getDefaultPriceMode();
		foreach ($shops as $shop)
		{
			$shop->setDisplayOutOfStock($displayoutofstock);
			$shop->setAllowOrderOutOfStock($alloworderoutofstock);
			$shop->setPriceMode($defaultPriceMode);
			$shop->save();
		}
		
		// Move shelves to catalog tree.
		$shelves = $ss->createQuery()->find();
		$this->log('Move shelves in catalog tree, mutate top shelves and attach them to their shops... (' . count($shelves) . ' shelves)');
		foreach ($shelves as $shelf)
		{
			$topic = $ds->getParentOf($shelf->getTopic(0));
			if (count($topic->getShelfArrayInverse()) == 0)
			{
				$this->insertShelfInTree($shelf, $rootFolder);
				$shelf = $pp->mutate($shelf, $tss->getNewDocumentInstance());
				$shop = $shs->getByTopic($topic);
				$shop->addTopShelf($shelf);
				try
				{
					$tm->beginTransaction();
					$pp->updateDocument($shop);
					$tm->commit();
				}
				catch (Exception $e)
				{
					$tm->rollBack($e);
				}
			}
		}
				
		// Update product structure.
		$this->log('Update products structure...');
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compiledbrandlabel;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product_i18n DROP COLUMN compiledbrandlabel_i18n;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compiledshelfid;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compiledshelflabel;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product_i18n DROP COLUMN compiledshelflabel_i18n;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compiledtopshelftopicid;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compiledtopshelforder;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compiledtopshelflabel;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product_i18n DROP COLUMN compiledtopshelflabel_i18n;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compiledcatalogid;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compiledtopicid;");		
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compileddefaultprice;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compileddefaultoldprice;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compileddefaultpriceinfo;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compileddefaultarticleid;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compileddefaultarticlelabel;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compileddefaultvisualid;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compileddefaultpricequantities;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compiledlimitedsalearticleid;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compiledarticles;");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN compiledavailablearticlecount;");
		
		$this->executeSQLQuery("ALTER TABLE `m_catalog_doc_product` ADD `shelf` int(11) default '0';");
		$this->executeSQLQuery("ALTER TABLE `m_catalog_doc_product` ADD `declination` int(11) default '0';");
		$this->executeSQLQuery("ALTER TABLE `m_catalog_doc_product` ADD `articleid` int(11) default '0';");
		$this->executeSQLQuery("ALTER TABLE `m_catalog_doc_product` ADD `codereference` varchar(255);");
		$this->executeSQLQuery("ALTER TABLE `m_catalog_doc_product` ADD `stocklevel` varchar(255);");
		$this->executeSQLQuery("ALTER TABLE `m_catalog_doc_product` ADD `stockquantity` float;");
		$this->executeSQLQuery("ALTER TABLE `m_catalog_doc_product` ADD `upsell` int(11) default '0'");

		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product CHANGE COLUMN crossselling complementary int(11) default '0';");
		$relationId = RelationService::getInstance()->getRelationId('complementary');
		$this->executeSQLQuery("UPDATE f_relation SET relation_name = 'complementary', relation_id = $relationId WHERE relation_name = 'crossSelling' AND document_model_id1 IN ('modules_catalog/simpleproduct', 'modules_catalog/declinedproduct');");
		
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product CHANGE COLUMN alternativeproduct similar int(11) default '0';");
		$relationId = RelationService::getInstance()->getRelationId('similar');
		$this->executeSQLQuery("UPDATE f_relation SET relation_name = 'similar', relation_id = $relationId WHERE relation_name = 'alternativeProduct' AND document_model_id1 IN ('modules_catalog/simpleproduct', 'modules_catalog/declinedproduct');");
		
		// Add stockAlertThreshold property.
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/simpleproduct.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'simpleproduct');
		$newProp = $newModel->getPropertyByName('stockAlertThreshold');
		$pp->addProperty('catalog', 'simpleproduct', $newProp);
		
		// Remove products from website tree and update properties.		
		$products = $ps->createQuery()->find();
		$this->log('Remove products from website tree and update properties... (' . count($products) . ' products)');
		$this->initTransformProductToSimpleproduct();
		$this->initTransformProductToDeclinedproduct();
		foreach ($products as $product)
		{
			
			$product->addShelf($this->getShelfByProduct($product));
			$product->save();
			$ts->deleteNode($ts->getInstanceByDocument($product));
			
			$allrelations = $pp->getChildRelationByMasterDocumentId($product->getId());
			$relations = array();
			foreach ($allrelations as $relation)
			{
				if($relation->getName() == 'article')
				{
					$relations[] = $relation;
				}
			}
			
			if (count($relations) == 1)
			{
				$product = $this->transformProductToSimpleproduct($product, f_util_ArrayUtils::firstElement($relations));
			}
			else if (count($relations) > 1)
			{
				$product = $this->transformProductToDeclinedproduct($product, $relations);
			}
		}		
		
		$this->executeSQLQuery("DELETE FROM f_relation WHERE relation_name = 'article' AND document_model_id1 IN ('modules_catalog/product', 'modules_catalog/simpleproduct', 'modules_catalog/declinedproduct');");
		$this->executeSQLQuery("ALTER TABLE m_catalog_doc_product DROP COLUMN article;");		
		
		// Import prices from old price documents.
		$this->log('Load data for prices...');
		$statement = $pp->executeSQLSelect('SELECT * FROM `m_catalog_doc_price`');
		$statement->execute();
		$result = $statement->fetchAll();
		
		$this->log('Delete old prices and update the price table structure...');
		$models = array('modules_catalog/price', 'modules_catalog/priceerp', 'modules_catalog/pricediscount', 'modules_catalog/pricediscounterp', 'modules_catalog/pricequantity', 'modules_catalog/pricequantityerp', 'modules_catalog/pricequantitydiscount', 'modules_catalog/pricequantitydiscounterp');
		foreach ($models as $model)
		{
			$this->executeSQLQuery("DELETE FROM f_relation WHERE relation_name = 'article' AND document_model_id1 = '$model';");
			$this->executeSQLQuery("DELETE FROM f_relation WHERE relation_name = 'zone' AND document_model_id1 = '$model';");
			$this->executeSQLQuery("DELETE FROM f_document WHERE document_model = '$model';");
		}
		$this->executeSQLQuery("DROP TABLE m_catalog_doc_price;");
		$this->executeSQLQuery("CREATE TABLE IF NOT EXISTS `m_catalog_doc_price` (
				`document_id` int(11) NOT NULL default '0',
				`document_model` varchar(50) NOT NULL default '',
				`document_label` varchar(255),
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
				`shopid` int(11),
				`productid` int(11),
				`taxcode` varchar(5),
				`valuewithtax` float,
				`valuewithouttax` float,
				`oldvaluewithtax` float,
				`oldvaluewithouttax` float,
				`discountdetail` varchar(255),
				`ecotax` float,
				`thresholdmin` int(11),
				`thresholdmax` int(11),
				`targetid` int(11),
				`priority` int(11),
				PRIMARY KEY  (`document_id`)
			) TYPE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin;"
		);
		
		//
		// /!\ 	WARNING: THIS IS A LIMITED MIGRATION FOR PRICES IGNORING SOME CASES:
		// 		- PRICES RELATED TO CUSTOMERS
		//  	- MULTIPLE CATALOGS WITH THE SAME SHIPPING ZONE
		//  	- PUBLICATION DATES
		//		- ... 
		// FOR PROJECTS REQUIRING BETTER IMPLEMENTATION, DO IT SPECIFICALY.
		//				
		$this->log('Create new prices... (' . count($result) . ' rows)');
		$pricesData = array();
		
		foreach ($result as $row)
		{
			$articleId = $row['article'];
			isset($pricesData[$articleId]) || $pricesData[$articleId] = array();
			if ($row['customer'] !== null)
			{
				continue;
			}
			$shopId = $this->getShopIdByBillingZoneId($row['zone']);
			if ($shopId === null)
			{
				continue;
			}
			isset($pricesData[$articleId][$shopId]) || $pricesData[$articleId][$shopId] = array();
			list(, $key) = explode('/', $row['document_model']);
			$key = str_replace('pricequantity', 'price', $key);
			$key = str_replace('pricediscount', 'discount', $key);
			isset($pricesData[$articleId][$shopId][$key]) || $pricesData[$articleId][$shopId][$key] = array();
			$threshold = ($row['threshold'] !== null) ? $row['threshold'] : 0;
			$pricesData[$articleId][$shopId][$key][$threshold] = $row;
		}
		f_util_FileUtils::append(WEBEDIT_HOME."/0301.log", var_export($pricesData, true));
		unset($result);
		foreach ($pricesData as $articleId => $pricesData1)
		{
			foreach ($pricesData1 as $shopId => $prices)
			{
				$shop = DocumentHelper::getDocumentInstance($shopId);
				$priceFormat = $shs->getPriceFormat($shop);
				$products = $as->getProductsByArticleIdAndShop($articleId, $shop);
				$basePrices = (isset($prices['priceerp']) && isset($prices['priceerp'][0])) ? $prices['priceerp'] : ((isset($prices['price']) && isset($prices['price'][0])) ? $prices['price'] : null);
				if ($basePrices === null)
				{
					$this->log('  -- No base price for article '.$articleId.' in shop '.$shopid);
					continue;
				}
				$discountPrices = (isset($prices['discounterp'])) ? $prices['discounterp'] : (isset($prices['discount'])) ? ($prices['discount']) : array();
				
				krsort($basePrices);
				foreach ($basePrices as $threshold => $basePrice)
				{
					$taxCode = $basePrice['taxcode'];
					$discountPrice = (isset($discountPrices[$threshold])) ? $discountPrices[$threshold] : null;
					$valueWithTax = catalog_PriceHelper::getValueWithTax($basePrice['value'], $taxCode, $shop);
					$valueWithoutTax = catalog_PriceHelper::getValueWithoutTax($basePrice['value'], $taxCode, $shop);
					if ($discountPrice !== null)
					{
						$value = $this->getDiscountPriceValue($discountPrice, $basePrice);
						$discountValueWithTax = catalog_PriceHelper::getValueWithTax($value, $taxCode, $shop);
						$discountValueWithoutTax = catalog_PriceHelper::getValueWithoutTax($value, $taxCode, $shop);
						switch ($discountPrice['discounttype'])
						{
							case catalog_DiscountHelper::TYPE_PERCENTAGE :
								$discountDetail = sprintf('%s%%', $discountPrice['value']);
								break;
				
							case catalog_DiscountHelper::TYPE_VALUE :
								$discountDetail = catalog_PriceHelper::applyFormat(0 - $value, $priceFormat);
								break;
							
							default :
								$discountDetail = null;
								break;
						}
					}
					foreach ($products as $product)
					{
						$normalPrice = catalog_PriceService::getInstance()->getNewDocumentInstance();
						$normalPrice->setShopId($shopId);
						$normalPrice->setProductId($product->getId());
						$normalPrice->setTaxCode($taxCode);
						if ($discountPrice !== null)
						{
							$normalPrice->setOldValueWithTax($valueWithTax);
							$normalPrice->setOldValueWithoutTax($valueWithoutTax);
							$normalPrice->setValueWithTax($discountValueWithTax);
							$normalPrice->setValueWithoutTax($discountValueWithoutTax);
							$normalPrice->setDiscountDetail($discountDetail);
						}
						else
						{
							$normalPrice->setValueWithTax($valueWithTax);
							$normalPrice->setValueWithoutTax($valueWithoutTax);
						}
						$normalPrice->setEcoTax($basePrice['ecotax']);
						$normalPrice->setThresholdMin($threshold);
						$normalPrice->save();
					}
				}
			}
		}
				
		// Generate compiled products.
		$cps->enableCompilation();
		foreach ($ps->createQuery()->find() as $product)
		{
			$cps->generateForProduct($product);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param f_persistentdocument_PersistentDocument $parent
	 */
	private function insertShelfInTree($shelf, $parent)
	{
		// Insert the shelf in the tree.
		$ts = TreeService::getInstance();
		$oldNode = $ts->getInstanceByDocument($shelf);
		if ($oldNode !== null)
		{
			$ts->deleteNode($oldNode);
		}
		$ts->newLastChildForNode($ts->getInstanceByDocument($parent), $shelf->getId());
		
		// Insert its sub-shelves.
		$sts = website_TopicService::getInstance();
		$ss = catalog_ShelfService::getInstance();
		$children = $sts->getChildrenOf($shelf->getTopic(0));
		$this->log(' shelfId = ' . $shelf->getId() . ' | topicId = ' . $shelf->getTopic(0)->getId() . ' | subTopics count = ' . count($children));
		foreach ($children as $child)
		{
			if ($child instanceof website_persistentdocument_systemtopic)
			{
				$this->insertShelfInTree($ss->getByTopic($child), $shelf);
			}
		}
	}
	
	/**
	 * Get the shelf for a product that is in website tree.
	 * @param catalog_persistentdocument_product $product
	 * @return catalog_persistentdocument_shelf
	 */
	private function getShelfByProduct($product)
	{
		$ss = catalog_ShelfService::getInstance();
		$query = $ss->createQuery();
		$query->createCriteria('topic')->add(Restrictions::parentOf($product->getId()));
		return $query->findUnique();
	}
	
	private $transformProductToSimpleproduct_propertiesName; 
	private $transformProductToSimpleproduct_i18NpropertiesName; 
	private function initTransformProductToSimpleproduct()
	{
		$sourceModel = f_persistentdocument_PersistentDocumentModel::getInstance('catalog', 'product');
		$destModel = f_persistentdocument_PersistentDocumentModel::getInstance('catalog', 'simpleproduct');
		$this->transformProductToSimpleproduct_propertiesName = array_diff(Transformers::buildCommonPropertiesArray($sourceModel, $destModel), array('documentversion'));
		$this->transformProductToSimpleproduct_i18NpropertiesName = array_diff(Transformers::buildCommonI18NPropertiesArray($sourceModel, $destModel), array('documentversion'));
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument  $sourceDocument
	 * @param f_persistentdocument_PersistentDocument $destDocument
	 * @param f_persistentdocument_PersistentRelation $relation
	 * @return catalog_persistentdocument_simpleproduct
	 */
	private function transformProductToSimpleproduct($sourceDocument, $relation)
	{
		$tm = f_persistentdocument_TransactionManager::getInstance();
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$sps = catalog_SimpleproductService::getInstance();
		
		try
		{
			$tm->beginTransaction();
			$destDocument = $sps->getNewDocumentInstance();
			Transformers::copyProperties($sourceDocument, $destDocument, $this->transformProductToSimpleproduct_propertiesName, $this->transformProductToSimpleproduct_i18NpropertiesName);
			$article = DocumentHelper::getDocumentInstance($relation->getDocumentId2());
			$destDocument->setArticleId($article->getId());
			$destDocument->setCodeReference($article->getCodeReference());
			$destDocument->setStockLevel($article->getStockLevel());
			$destDocument->setStockQuantity($article->getStockQuantity());
			
			if($article->getVisual() !== null)
			{
				$articleVisual = $article->getVisual();
				if($destDocument->getVisual() === null)
				{
					$destDocument->setVisual($articleVisual);
				}
			}
			
			$destDocument = $pp->mutate($sourceDocument, $destDocument);
			$destDocument->save();
			$tm->commit();
			return $destDocument;
		}
		catch (Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return null;
	}
		
	private $transformProductToDeclinedproduct_propertiesName; 
	private $transformProductToDeclinedproduct_i18NpropertiesName; 
	private function initTransformProductToDeclinedproduct()
	{
		$sourceModel = f_persistentdocument_PersistentDocumentModel::getInstance('catalog', 'product');
		$destModel = f_persistentdocument_PersistentDocumentModel::getInstance('catalog', 'declinedproduct');
		$this->transformProductToDeclinedproduct_propertiesName = array_diff(Transformers::buildCommonPropertiesArray($sourceModel, $destModel), array('documentversion'));
		$this->transformProductToDeclinedproduct_i18NpropertiesName = array_diff(Transformers::buildCommonI18NPropertiesArray($sourceModel, $destModel), array('documentversion'));
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument  $sourceDocument
	 * @param f_persistentdocument_PersistentDocument $destDocument
	 * @param f_persistentdocument_PersistentRelation[] $relations
	 * @return catalog_persistentdocument_declinedproduct
	 */
	private function transformProductToDeclinedproduct($sourceDocument, $relations)
	{
		$tm = f_persistentdocument_TransactionManager::getInstance();
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$dps = catalog_DeclinedproductService::getInstance();
		$pds = catalog_ProductdeclinationService::getInstance();
		try
		{
			$tm->beginTransaction();
			$destDocument = $dps->getNewDocumentInstance();
			Transformers::copyProperties($sourceDocument, $destDocument, $this->transformProductToDeclinedproduct_propertiesName, $this->transformProductToDeclinedproduct_i18NpropertiesName);
			foreach ($relations as $relation)
			{
				$article = DocumentHelper::getDocumentInstance($relation->getDocumentId2());
				$declination = $pds->getNewDocumentInstance();
				$declination->setArticleId($article->getId());
				$declination->setLabel($article->getLabel());
				$declination->setVisual($article->getVisual());
				$declination->setCodeReference($article->getCodeReference());
				$declination->setStockLevel($article->getStockLevel());
				$declination->setStockQuantity($article->getStockQuantity());
				$destDocument->addDeclination($declination);
			}
			$destDocument = $pp->mutate($sourceDocument, $destDocument);
			$destDocument->save();
			$tm->commit();
			return $destDocument;
		}
		catch (Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return null;
	}

	private $shopIdByZoneId = array();
		
	
	/**
	 * @param Integer $zoneId
	 * @return Integer
	 */
	private function getShopIdByBillingZoneId($zoneId)
	{
		if (!isset($this->shopIdByZoneId[$zoneId]))
		{
			$shop = f_util_ArrayUtils::firstElement(catalog_ShopService::getInstance()->createQuery()->add(Restrictions::eq('billingZone', $zoneId))->find());
			$this->shopIdByZoneId[$zoneId] = ($shop === null) ? null : $shop->getId();
		}
		return $this->shopIdByZoneId[$zoneId];
	}
	
	/**
	 * @param Array $discountPriceData
	 * @param Array $basePriceData
	 * @return Double
	 */
	private function getDiscountPriceValue($discountPriceData, $basePriceData)
	{
		return catalog_DiscountHelper::applyDiscountOnPrice($basePriceData['value'], $discountPriceData['value'], $discountPriceData['discounttype']);
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
		return '0301';
	}
}