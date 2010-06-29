<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_attributefolder
 * @package catalog.persistentdocument
 */
class catalog_persistentdocument_attributefolder extends catalog_persistentdocument_attributefolderbase 
{
	/**
	 * cached version of serializedattributes property
	 * @var array
	 */
	private $unserializedAttributes;
	
	/**
	 * @return array
	 */
	public final function getAttributes()
	{
		if ($this->unserializedAttributes === null)
		{
			// set cache
			$val = $this->getSerializedattributes();
			if (f_util_StringUtils::isEmpty($val))
			{
				$this->unserializedAttributes = array();
			}
			else
			{
				$this->unserializedAttributes = unserialize($val);
			}
		}
		
		return $this->unserializedAttributes;
	}
	
	/**
	 * @param array $attributes
	 */
	public final function setAttributes($attributes)
	{
		// reset cache
		$this->unserializedAttributes = null;
		
		if (f_util_ArrayUtils::isEmpty($attributes))
		{
			$this->setSerializedattributes(null);
		}
		else
		{
			$this->setSerializedattributes(serialize($attributes));
		}
	}	
	/**
	 * Return JSON String
	 * @return string
	 */
	public function getAttributesJSON()
	{
		$attributes = $this->getAttributes();
		if (count($attributes))
		{
			return JsonService::getInstance()->encode($attributes);
		}
		return null;
	}
	
	/**
	 * @param string $json
	 */
	public function setAttributesJSON($json)
	{
		if (f_util_StringUtils::isEmpty($json))
		{
			$this->setSerializedattributes(null);
		}
		else
		{
			$attributes = JsonService::getInstance()->decode($json);
			$this->setAttributes($attributes);
		}
	}
}