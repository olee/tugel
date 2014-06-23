<?php

namespace Zeutzheim\PpintBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * ZeutzheimPpintBundle:Version
 *
 * @ORM\Entity
 * @ORM\Table(name="version")
 */
class Version {

	/**
	 * @var string
	 *
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Package", cascade={"all"})
	 * @ORM\JoinColumn(name="package_id", nullable=false)
	 */
	private $package;

	/**
	 * @var string
	 *
	 * @ORM\Id
	 * @ORM\Column(name="name", type="string", length=30, nullable=false)
	 */
	private $name;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="date", type="datetime")
	 */
	private $lastModifiedDate;

	//*******************************************************************

	/**
	 * Set package
	 *
	 * @param \Zeutzheim\PpintBundle\Entity\Package $package
	 * @return Version
	 */
	public function setPackage(\Zeutzheim\PpintBundle\Entity\Package $package) {
		$this->package = $package;
		return $this;
	}

	/**
	 * Get package
	 *
	 * @return \Zeutzheim\PpintBundle\Entity\Package
	 */
	public function getPackage() {
		return $this->package;
	}

	//*******************************************************************
	
	/**
	 * Set name
	 *
	 * @param string $name
	 * @return Package
	 */
	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	//*******************************************************************

	/**
	 * Set lastModifiedDate
	 *
	 * @param \DateTime $lastModifiedDate
	 * @return Version
	 */
	public function setLastModifiedDate($lastModifiedDate) {
		$this->lastModifiedDate = $lastModifiedDate;
		return $this;
	}

	/**
	 * Get lastModifiedDate
	 *
	 * @return \DateTime
	 */
	public function getLastModifiedDate() {
		return $this->lastModifiedDate;
	}

}
