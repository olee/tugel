<?php

namespace Tugel\TugelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

use Tugel\TugelBundle\Model\AbstractPlatform;

/**
 * TugelBundle:Platform
 *
 * @ORM\Entity
 * @ORM\Table(name="platform")
 */
class Platform {
	/**
	 * @var integer
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="name", type="string", length=255, nullable=false)
	 */
	private $name;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\OneToMany(targetEntity="Package", mappedBy="platform", fetch="EXTRA_LAZY", cascade={"all"}, orphanRemoval=true)
	 */
	private $packages;

	//*******************************************************************

	/**
	 * @var AbstractPlatform
	 */
	public $platform;

	//*******************************************************************
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->packages = new \Doctrine\Common\Collections\ArrayCollection();
		$this->languages = new \Doctrine\Common\Collections\ArrayCollection();
	}

	public function __toString() {
		return $this->name;
	}
	
	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return AbstractPlatform
	 */
	public function getPlatform() {
		return $this->platform;
	}

	//*******************************************************************

	/**
	 * Set name
	 *
	 * @param string $name
	 * @return Platform
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
	 * Add packages
	 *
	 * @param \Tugel\TugelBundle\Entity\Package $packages
	 * @return Platform
	 */
	public function addPackage(\Tugel\TugelBundle\Entity\Package $packages) {
		$this->packages[] = $packages;
		return $this;
	}

	/**
	 * Remove packages
	 *
	 * @param \Tugel\TugelBundle\Entity\Package $packages
	 */
	public function removePackage(\Tugel\TugelBundle\Entity\Package $packages) {
		$this->packages->removeElement($packages);
	}

	/**
	 * Get packages
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getPackages() {
		return $this->packages;
	}

	//*******************************************************************
}
