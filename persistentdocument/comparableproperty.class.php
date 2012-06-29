<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_comparableproperty
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_comparableproperty extends catalog_persistentdocument_comparablepropertybase 
{
	/**
	 * @return mixed[]
	 */
	public function getParameters()
	{
		$ser = $this->getParametersSerialized();
		if ($ser)
		{
			return unserialize($ser);
		}
		return array();
	}
	
	/**
	 * @param mixed[] $parameters
	 */
	public function setParameters($parameters)
	{
		if (!is_array($parameters))
		{
			$parameters = null;
		}
		$this->setParametersSerialized(serialize($parameters));
	}
	
	/**
	 * @return catalog_ComparableProperty
	 */
	public function getPropertyInstance()
	{
		$className = $this->getClassName();
		return new $className($this->getParameters());
	}
}