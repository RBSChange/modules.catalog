<?php
class catalog_ScriptParameterElement extends import_ScriptBaseElement
{
	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->getComputedAttribute('value');
	}
}