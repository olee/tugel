<?php

namespace Zeutzheim\PpintBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * ZeutzheimPpintBundle:Platform
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
	 * @var string
	 *
	 * @ORM\Column(name="base_url", type="string", length=255, nullable=false)
	 */
	private $baseUrl;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="crawl_url", type="string", length=255, nullable=true)
	 */
	private $crawlUrl;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="crawl_type", type="string", length=6, nullable=true)
	 */
	private $crawlType;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\OneToMany(targetEntity="Package", mappedBy="platform")
	 */
	private $packages;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\ManyToMany(targetEntity="Language", inversedBy="platforms")
	 * @ORM\JoinTable(name="platform_language")
	 */
	private $languages;

	//*******************************************************************

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->packages = new \Doctrine\Common\Collections\ArrayCollection();
		$this->languages = new \Doctrine\Common\Collections\ArrayCollection();
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
	 * Set baseUrl
	 *
	 * @param string $baseUrl
	 * @return Platform
	 */
	public function setBaseUrl($baseUrl) {
		$this->baseUrl = $baseUrl;
		return $this;
	}

	/**
	 * Get baseUrl
	 *
	 * @return string
	 */
	public function getBaseUrl() {
		return $this->baseUrl;
	}

	//*******************************************************************

	/**
	 * Set crawlUrl
	 *
	 * @param string $crawlUrl
	 * @return Platform
	 */
	public function setCrawlUrl($crawlUrl) {
		$this->crawlUrl = $crawlUrl;
		return $this;
	}

	/**
	 * Get crawlUrl
	 *
	 * @return string
	 */
	public function getCrawlUrl() {
		return $this->crawlUrl;
	}

	//*******************************************************************

	/**
	 * Set crawlType
	 *
	 * @param string $crawlType
	 * @return Platform
	 */
	public function setCrawlType($crawlType) {
		$this->crawlType = $crawlType;
		return $this;
	}

	/**
	 * Get crawlType
	 *
	 * @return string
	 */
	public function getCrawlType() {
		return $this->crawlType;
	}

	//*******************************************************************

	/**
	 * Add packages
	 *
	 * @param \Zeutzheim\PpintBundle\Entity\Package $packages
	 * @return Platform
	 */
	public function addPackage(\Zeutzheim\PpintBundle\Entity\Package $packages) {
		$this->packages[] = $packages;
		return $this;
	}

	/**
	 * Remove packages
	 *
	 * @param \Zeutzheim\PpintBundle\Entity\Package $packages
	 */
	public function removePackage(\Zeutzheim\PpintBundle\Entity\Package $packages) {
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

	/**
	 * Add languages
	 *
	 * @param \Zeutzheim\PpintBundle\Entity\Language $languages
	 * @return Platform
	 */
	public function addLanguage(\Zeutzheim\PpintBundle\Entity\Language $languages) {
		$this->languages[] = $languages;
		return $this;
	}

	/**
	 * Remove languages
	 *
	 * @param \Zeutzheim\PpintBundle\Entity\Language $languages
	 */
	public function removeLanguage(\Zeutzheim\PpintBundle\Entity\Language $languages) {
		$this->languages->removeElement($languages);
	}

	/**
	 * Get languages
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getLanguages() {
		return $this->languages;
	}

	//*******************************************************************

}
