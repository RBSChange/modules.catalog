<?php
/**
 * catalog_VirtualproductService
 * @package modules.catalog
 */
class catalog_VirtualproductService extends catalog_ProductService
{
	/**
	 * @var catalog_VirtualproductService
	 */
	private static $instance;

	/**
	 * @return catalog_VirtualproductService
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
	 * @return catalog_persistentdocument_virtualproduct
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/virtualproduct');
	}

	/**
	 * Create a query based on 'modules_catalog/virtualproduct' model.
	 * Return document that are instance of modules_catalog/virtualproduct,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/virtualproduct');
	}
	
	/**
	 * Create a query based on 'modules_catalog/virtualproduct' model.
	 * Only documents that are strictly instance of modules_catalog/virtualproduct
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/virtualproduct', false);
	}
	
	/**
	 * @param catalog_persistentdocument_virtualproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		parent::preSave($document, $parentNodeId);
		$media = $document->getSecureMedia();
		if (!($media instanceof media_persistentdocument_securemedia)) 
        {
        	if ($media instanceof media_persistentdocument_media)
        	{
        		$media = $media->getDocumentService()->transform($media, 'modules_media/securemedia');
        	}
        	else
        	{
        		$media = null;
        	}
        	$document->setSecureMedia($media);
        }
        
        if ($media !== null)
        {
        	$this->updateMediaInfos($document, $media);
        }    
	}
	
	/**
	 * Mise à jour des informations des medias attaché à la 
	 *
	 * @param catalog_persistentdocument_virtualproduct $document
	 * @param media_persistentdocument_securemedia $media
	 */
	private function updateMediaInfos($document, $media)
	{
	    if ($media->isContextLangAvailable())
        {
            $media->setLabel($document->getLabel());
            $media->setTitle($document->getCodeReference());
            $media->setDescription($document->getDescription());
            if ($media->isModified())
            {
                 $media->save();   
            }
        }
	}
	
	/**
	 * 
	 * @param catalog_persistentdocument_virtualproduct $product
	 * @param customer_persistentdocument_customer $customer
	 * @param integer $expeditionId
	 * @param integer $expeditionLineId
	 */
	public function hasMediaAccess($product, $customer, $expeditionId, $expeditionLineId)
	{
		$expeditionLine = DocumentHelper::getDocumentInstance($expeditionLineId, 'modules_order/expeditionline');
		if ($expeditionLine->hasMeta('MediaAccessGranted'))
		{
			$expeditionLine->setMeta('MediaAccessGranted', $expeditionLine->getMeta('MediaAccessGranted') + 1);
		}
		else
		{
			$expeditionLine->setMeta('MediaAccessGranted', 1);
		}
		$expeditionLine->saveMeta();
		return true;
	}

	/**
	 * @param catalog_persistentdocument_virtualproduct $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		$result = parent::isPublishable($document);
		if ($result)
		{
			if ($document->getSecureMedia() === null)
			{
				$this->setActivePublicationStatusInfo($document, '&modules.catalog.bo.general.Has-no-securemedia;');
				return false;
			}
		}
		return $result;
	}

	/**
	 * @return boolean
	 * @see catalog_ModuleService::getProductModelsThatMayAppearInCarts()
	 */
	public function mayAppearInCarts()
	{
		return true;
	}
	
	/**
	 * @return array
	 */
	public function getPropertyNamesForMarkergas()
	{
		return array('catalog/virtualproduct' => array(
			'label' => 'label',
			'brand' => 'brand/label',
			'codeReference' => 'codeReference'
		));
	}
}