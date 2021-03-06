<?php

/**
 * @package Newscoop
 */

namespace Newscoop\Entity;

use Doctrine\ORM\Mapping AS ORM;
use Newscoop\Utils\Validation;

/**
 * Provides the basic container for an entity that has a primary key.
 *
 * @ORM\MappedSuperclass
 */
class Entity {

	/**
	 * @ORM\Id @ORM\GeneratedValue
	 * @ORM\Column(name="id", type="integer")
	 * @var int
	 */
	protected $id;

	/* --------------------------------------------------------------- */

	/**
	 * Provides the id of the output, this will uniquielly identify this output.
	 *
	 * @return integer
	 *		The id of the output.
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set the id of the output, this will uniquielly identify this output.
	 *
	 * @param string $p_id
	 *		The id of the output, must not be null or empty.
	 *
	 * @return Newscoop\Entity\Entity
	 *		This object for chaining purposes.
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/* --------------------------------------------------------------- */
}
