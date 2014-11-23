<?php

namespace Zeutzheim\PpintBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * ZeutzheimPpintBundle:Package
 *
 * @ORM\Entity
 * @ORM\Table(name="package", options={"collate"="utf8_bin"})
 */
class Package {
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
	 * @ORM\Column(name="description", type="string", length=1024, nullable=true)
	 */
	private $description;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="url", type="string", length=255, nullable=false)
	 */
	private $url;

	/**
	 * @var \Zeutzheim\PpintBundle\Entity\Platform
	 *
	 * @ORM\ManyToOne(targetEntity="Platform", inversedBy="packages")
	 * @ORM\JoinColumn(name="platform_id", nullable=false)
	 * @JMS\Exclude
	 */
	private $platform;

	/**
	 * @var \Zeutzheim\PpintBundle\Entity\Platform
	 *
	 * @ORM\OneToMany(targetEntity="Version", mappedBy="package", fetch="EXTRA_LAZY", cascade={"all"}, orphanRemoval=true)
     * @ORM\OrderBy({"addedDate" = "ASC"})
	 * @JMS\Exclude
	 */
	private $versions;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="mv_tag", type="string", length=127, nullable=true)
	 */
	private $masterVersionTag;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="crawled", type="boolean", nullable=false)
	 */
	private $crawled;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="indexed", type="boolean", nullable=false)
	 */
	private $indexed;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="error", type="boolean", nullable=false)
	 */
	private $error;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="added_date", type="datetime", nullable=false)
	 */
	private $addedDate;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="indexed_date", type="datetime", nullable=true)
	 */
	private $indexedDate;

	//*******************************************************************

	public function __construct() {
		$this->crawled = false;
		$this->indexed = false;
		$this->error = false;
		$this->addedDate = new \DateTime();
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
	 * Set description
	 * @param string $description
	 * @return Format
	 */
	public function setDescription($description) {
		$this->description = $description;
		return $this;
	}

	/**
	 * Get description
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	//*******************************************************************

	/**
	 * Set url
	 *
	 * @param string $name
	 * @return Package
	 */
	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	/**
	 * Get url
	 *
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	//*******************************************************************

	/**
	 * Set platform
	 *
	 * @param \Zeutzheim\PpintBundle\Entity\Platform $platform
	 * @return Package
	 */
	public function setPlatform(\Zeutzheim\PpintBundle\Entity\Platform $platform) {
		$this->platform = $platform;
		return $this;
	}

	/**
	 * Get platform
	 *
	 * @return \Zeutzheim\PpintBundle\Entity\Platform
	 */
	public function getPlatform() {
		return $this->platform;
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
	public function isCrawled() {
		return $this->crawled;
	}

	//*******************************************************************

	/**
	 * Set indexed
	 *
	 * @param boolean $indexed
	 * @return Version
	 */
	public function setIndexed($indexed) {
		$this->indexed = $indexed;
		$this->indexedDate = new \DateTime();
		return $this;
	}

	/**
	 * Get indexed
	 *
	 * @return boolean
	 */
	public function isIndexed() {
		return $this->indexed;
	}

	//*******************************************************************

	/**
	 * Set error
	 *
	 * @param boolean $error
	 * @return Version
	 */
	public function setError($error) {
		$this->error = $error;
		return $this;
	}

	/**
	 * Get error
	 *
	 * @return boolean
	 */
	public function isError() {
		return $this->error;
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

	/**
	 * Set indexedDate
	 *
	 * @param \DateTime $indexedDate
	 * @return Version
	 */
	public function setIndexedDate($indexedDate) {
		$this->indexedDate = $indexedDate;
		return $this;
	}

	/**
	 * Get indexedDate
	 *
	 * @return \DateTime
	 */
	public function getIndexedDate() {
		return $this->indexedDate;
	}

	//*******************************************************************

	/**
	 * Add versions
	 *
	 * @param \Zeutzheim\PpintBundle\Entity\Version $versions
	 * @return Package
	 */
	public function addVersion(\Zeutzheim\PpintBundle\Entity\Version $versions) {
		$this->versions[] = $versions;
		return $this;
	}

	/**
	 * Remove versions
	 *
	 * @param \Zeutzheim\PpintBundle\Entity\Version $versions
	 */
	public function removeVersion(\Zeutzheim\PpintBundle\Entity\Version $versions) {
		$this->versions->removeElement($versions);
	}

	/**
	 * Get versions
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getVersions() {
		return $this->versions;
	}

	//*******************************************************************

	/**
	 * Set masterVersionTag
	 *
	 * @param string $masterVersionTag
	 * @return Package
	 */
	public function setMasterVersionTag($masterVersionTag) {
		$this->masterVersionTag = $masterVersionTag;
		return $this;
	}

	/**
	 * Get masterVersionTag
	 *
	 * @return string
	 */
	public function getMasterVersionTag() {
		return $this->masterVersionTag;
	}

	//*******************************************************************
}
