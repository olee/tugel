<?php

namespace Zeutzheim\PpintBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ZeutzheimPpintBundle:CodeTag
 *
 * @ORM\Table(name="codetag")
 * @ORM\Entity
 */
class CodeTag
{

	/**
	 * @var string
	 *
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Package", inversedBy="codeTags", cascade={"all"}, fetch="EXTRA_LAZY")
	 * @ORM\JoinColumn(name="package_id", nullable=false)
	 */
	private $package;

	/**
	 * @var string
	 *
	 * @ORM\Id
	 * @ORM\Column(name="name", type="string", length=128, nullable=false)
	 */
	private $name;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="count", type="float", nullable=false)
	 */
	private $count;

	public function __construct(\Zeutzheim\PpintBundle\Entity\Package $package, $name, $count = 1) {
		$this->package = $package;
		$this->name = $name;
		$this->count = $count;
	}

	/**
	 * Get pseudo-id
	 */
	public function getId()
	{
		return $this->package->getId() . ":" . $this->name;
	}

	/**
	 * Set package
	 *
	 * @param \Zeutzheim\PpintBundle\Entity\Package $package
	 * @return CodeTag
	 */
	public function setPackage(\Zeutzheim\PpintBundle\Entity\Package $package)
	{
		$this->package = $package;
		return $this;
	}

	/**
	 * Get package
	 *
	 * @return \Zeutzheim\PpintBundle\Entity\Package 
	 */
	public function getPackage()
	{
		return $this->package;
	}

	/**
	 * Set name
	 *
	 * @param string $name
	 * @return CodeTag
	 */
	public function setName($name)
	{
		$this->name = $name;
	
		return $this;
	}

	/**
	 * Get name
	 *
	 * @return string 
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set count
	 *
	 * @param integer $count
	 * @return CodeTag
	 */
	public function setCount($count)
	{
		$this->count = $count;
	
		return $this;
	}

	/**
	 * Get count
	 *
	 * @return integer 
	 */
	public function getCount()
	{
		return $this->count;
	}

}
