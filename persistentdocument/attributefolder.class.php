<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_attributefolder
 * @package catalog.persistentdocument
 */
class catalog_persistentdocument_attributefolder extends catalog_persistentdocument_attributefolderbase 
{
	
	/**
	 * @return array
	 */
	public final function getAttributes()
	{
		$val = $this->getSerializedattributes();
		if (f_util_StringUtils::isEmpty($val))
		{
			return array();
		}
		return unserialize($val);
	}
	
	/**
	 * @param array $attributes
	 */
	public final function setAttributes($attributes)
	{
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