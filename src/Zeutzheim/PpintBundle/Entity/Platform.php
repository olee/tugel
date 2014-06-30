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
	 * @ORM\Column(name="crawl_url", type="string", length=255, nullable=false)
	 */
	private $crawlUrl;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="package_regex", type="string", length=511, nullable=false)
	 */
	private $packageRegex;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="version_regex", type="string", length=511, nullable=false)
	 */
	private $versionRegex;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="mv_tag_regex", type="string", length=511, nullable=false)
	 */
	private $masterVersionTagRegex;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="master_version", type="string", length=64, nullable=true)
	 */
	private $masterVersion;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\OneToMany(targetEntity="Package", mappedBy="platform", fetch="EXTRA_LAZY")
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
	 * Set packageRegex
	 *
	 * @param string $crawlType
	 * @return Platform
	 */
	public function setPackageRegex($packageRegex) {
		$this->packageRegex = $packageRegex;
		return $this;
	}

	/**
	 * Get packageRegex
	 *
	 * @return string
	 */
	public function getPackageRegex() {
		return $this->packageRegex;
	}

	//*******************************************************************

	/**
	 * Set versionRegex
	 *
	 * @param string $crawlType
	 * @return Platform
	 */
	public function setVersionRegex($versionRegex) {
		$this->versionRegex = $versionRegex;
		return $this;
	}

	/**
	 * Get versionRegex
	 *
	 * @return string
	 */
	public function getVersionRegex() {
		return $this->versionRegex;
	}
	//*******************************************************************

	/**
	 * Set masterVersionTagRegex
	 *
	 * @param string $masterVersionTagRegex
	 * @return Platform
	 */
	public function setMasterVersionTagRegex($masterVersionTagRegex) {
		$this->masterVersionTagRegex = $masterVersionTagRegex;
		return $this;
	}

	/**
	 * Get masterVersionTagRegex
	 *
	 * @return string
	 */
	public function getMasterVersionTagRegex() {
		return $this->masterVersionTagRegex;
	}


	//*******************************************************************

	/**
	 * Set masterVersion
	 *
	 * @param string $masterVersion
	 * @return Platform
	 */
	public function setMasterVersion($masterVersion) {
		$this->masterVersion = $masterVersion;
		return $this;
	}

	/**
	 * Get masterVersion
	 *
	 * @return string
	 */
	public function getMasterVersion() {
		return $this->masterVersion;
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
