<?php

namespace Migration\Extensions;

use Nette\Object;
use Migration;


/**
 * @author Mikulas Dite
 */
class Enumerator extends Object implements Migration\IExtension
{

	/**
	 * @param array name => value
	 */
	public function __construct(array $parameters = array())
	{
		foreach ($parameters as $name => $value)
		{
			$this->addParameter($name, $value);
		}
	}

	/**
	 * @param string
	 * @param mixed
	 * @return self $this
	 */
	public function addParameter($name, $value)
	{
		$this->parameters[$name] = $value;
		return $this;
	}

	/**
	 * @return array name => value
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * Unique extension name.
	 * @return string
	 */
	public function getName()
	{
		return 'sql';
	}

	public function __toString()
	{
		return $this->getName();
	}

	/**
	 * @param Migration\File
	 * @return int number of queries
	 */
	public function execute(Migration\File $sql) {}

}
