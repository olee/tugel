<?php

namespace Zeutzheim\PpintBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * ZeutzheimPpintBundle:Version
 *
 * @ORM\Entity
 * @ORM\Table(name="version", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="version_idx", columns={"package_id", "name"})
 * })
 */
class Version {
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
	 * @ORM\ManyToOne(targetEntity="Package", cascade={"all"}, fetch="EXTRA_LAZY")
	 * @ORM\JoinColumn(name="package_id", nullable=false)
	 */
	private $package;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="name", type="string", length=62, nullable=false)
	 */
	private $name;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="crawled", type="boolean", nullable=false)
	 */
	private $crawled;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="added_date", type="datetime")
	 */
	private $addedDate;

	//*******************************************************************

	public function __construct() {
		$this->crawled = false;
		$this->addedDate = new \DateTime();
	}

	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

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
	 * Set crawled
	 *
	 * @param boolean $crawled
	 * @return Version
	 */
	public function setCrawled($crawled) {
		$this->crawled = $crawled;
		return $this;
	}

	/**
	 * Get crawled
	 *
	 * @return boolean
	 */
	public function getCrawled() {
		return $this->crawled;
	}

	//*******************************************************************

	/**
	 * Set addedDate
	 *
	 * @param \DateTime $addedDate
	 * @return Version
	 */
	public function setAddedDate($addedDate) {
		$this->addedDate = $addedDate;
		return $this;
	}

	/**
	 * Get addedDate
	 *
	 * @return \DateTime
	 */
	public function getAddedDate() {
		return $this->addedDate;
	}

	//*******************************************************************
}
