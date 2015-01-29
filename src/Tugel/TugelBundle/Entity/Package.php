<?php

namespace Tugel\TugelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

use Tugel\TugelBundle\Model\AbstractPlatform;

/**
 * TugelBundle:Package
 *
 * @ORM\Entity
 * @ORM\Table(
 * 		name = "package",
 * 		options = {"collate" = "utf8_bin"},
 * 		uniqueConstraints = { @ORM\UniqueConstraint(name = "package_unique", columns = {"platform_id", "name"})}
 * )
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
	 * @var \Tugel\TugelBundle\Entity\Platform
	 *
	 * @ORM\ManyToOne(targetEntity="Platform", inversedBy="packages")
	 * @ORM\JoinColumn(name="platform_id", nullable=false)
	 * @JMS\Exclude
	 */
	private $platform;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="name", type="string", length=255, nullable=false)
	 */
	private $name;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="added_date", type="datetime", nullable=false)
	 */
	private $addedDate;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="indexed_date", type="datetime", nullable=true)
	 */
	private $indexedDate;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="error", type="integer", nullable=true)
	 * @JMS\Exclude
	 */
	private $error;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="new", type="boolean", nullable=false)
	 * @JMS\Exclude
	 */
	private $new;

	//*******************************************************************

	/**
	 * @var string
	 *
	 * @ORM\Column(name="version", type="string", length=32, nullable=true)
	 */
	private $version;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="version_ref", type="string", length=64, nullable=true)
	 * @JMS\Exclude
	 */
	private $versionReference;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\ManyToMany(targetEntity="Package", inversedBy="dependencyOf", fetch="EXTRA_LAZY")
	 * @ORM\JoinTable(name="package_dependencies",
	 * 		joinColumns = {@ORM\JoinColumn(name="package_id", referencedColumnName="id")},
	 * 		inverseJoinColumns = {@ORM\JoinColumn(name="depends_on", referencedColumnName="id")}
	 * )
	 * @ORM\OrderBy({"name" = "ASC"})
	 * @JMS\Groups({"details"})
	 */
	private $dependencies;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\ManyToMany(targetEntity="Package", mappedBy="dependencies", fetch="EXTRA_LAZY")
	 * @ORM\OrderBy({"name" = "ASC"})
	 * @JMS\Exclude
	 */
	private $dependencyOf;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="description", type="text", nullable=true)
	 * @JMS\Groups({"details", "description"})
	 */
	private $description;

	//*******************************************************************

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\OneToMany(targetEntity="CodeTag", mappedBy="package", fetch="EXTRA_LAZY", cascade={"all"}, orphanRemoval=true)
	 * @ORM\OrderBy({"count" = "DESC", "name" = "ASC"})
	 * @JMS\Groups({"details"})
	 */
	private $codeTags;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="codetag_max", type="integer", nullable=true)
	 * @JMS\Groups({"details"})
	 */
	private $codeTagsMaximum;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="codetagstext", type="text", nullable=true)
	 * @JMS\Exclude
	 */
	private $codeTagsText;

	//*******************************************************************

	/**
	 * @var string
	 *
	 * @ORM\Column(name="license", type="string", length=255, nullable=true)
	 * @JMS\Groups({"details"})
	 */
	private $license;

	//*******************************************************************
	
	/**
	 * @var string
	 *
	 * @ORM\Column(name="namespaces", type="text", nullable=true)
	 * @JMS\Groups({"details"})
	 */
	private $namespaces;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="classes", type="text", nullable=true)
	 * @JMS\Groups({"details"})
	 */
	private $classes;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="languages", type="string", length=1024, nullable=true)
	 */
	private $languages;

	//*******************************************************************

	/**
	 * @var float
	 *
	 */
	public $_score;

	/**
	 * @var float
	 *
	 */
	public $_percentScore;

	/**
	 * @var array
	 *
	 * @JMS\Exclude
	 */
	public $data;

	//*******************************************************************

	public function __construct() {
		$this->codeTags = new \Doctrine\Common\Collections\ArrayCollection();
		$this->dependencies = new \Doctrine\Common\Collections\ArrayCollection();
		$this->dependencyOf = new \Doctrine\Common\Collections\ArrayCollection();
		$this->new = true;
		$this->error = null;
		$this->addedDate = new \DateTime();
	}

	public function __toString() {
		if ($this->version)
			return $this->name . ':' . $this->version;
		else
			return $this->name;
	}

	/**
	 * Get indexed
	 *
	 * @return boolean
	 */
	public function isIndexed() {
		return $this->version && (!$this->error || $this->error == AbstractPlatform::ERR_NEEDS_REINDEXING);
	}

	public function getUrl() {
		return $this->platform->getPlatform()->getPackageUrl($this);
	}

	public function getCachePath() {
		return WEB_DIRECTORY . '../tmp/' . $this->getPlatform()->getName() . '/' . str_replace(':', '_', $this->getName()) . '/';
	}

	//*******************************************************************

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
	 * Set platform
	 *
	 * @param \Tugel\TugelBundle\Entity\Platform $platform
	 * @return Package
	 */
	public function setPlatform(\Tugel\TugelBundle\Entity\Platform $platform) {
		$this->platform = $platform;
		return $this;
	}

	/**
	 * Get platform
	 *
	 * @return \Tugel\TugelBundle\Entity\Platform
	 */
	public function getPlatform() {
		return $this->platform;
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
	 * Set addedDate
	 *
	 * @param \DateTime $addedDate
	 * @return Package
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
	 * Set new
	 *
	 * @param boolean $new
	 * @return Package
	 */
	public function setNew($new) {
		$this->new = $new;
		return $this;
	}

	/**
	 * Get new
	 *
	 * @return boolean
	 */
	public function getNew() {
		return $this->new;
	}

	//*******************************************************************

	/**
	 * Set version
	 *
	 * @param string $version
	 * @return Package
	 */
	public function setVersion($version) {
		$this->version = $version;
		return $this;
	}

	/**
	 * Get version
	 *
	 * @return string
	 */
	public function getVersion() {
		return $this->version;
	}

	//*******************************************************************

	/**
	 * Set version reference
	 *
	 * @param string $versionReference
	 * @return Package
	 */
	public function setVersionReference($versionReference) {
		$this->versionReference = $versionReference;
		return $this;
	}

	/**
	 * Get version reference
	 *
	 * @return string
	 */
	public function getVersionReference() {
		return $this->versionReference;
	}

	//*******************************************************************

	/**
	 * Set indexedDate
	 *
	 * @param \DateTime $indexedDate
	 * @return Package
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
	 * Set error
	 *
	 * @param boolean $error
	 * @return Package
	 */
	public function setError($error) {
		$this->error = $error;
		return $this;
	}

	/**
	 * Get error
	 *
	 * @return integer
	 */
	public function getError() {
		return $this->error;
	}

	//*******************************************************************
	//*******************************************************************

	/**
	 * Set namespaces
	 *
	 * @param string $namespaces
	 * @return Package
	 */
	public function setNamespaces($namespaces) {
		$this->namespaces = $namespaces;
		return $this;
	}

	/**
	 * Get namespaces
	 *
	 * @return string
	 */
	public function getNamespaces() {
		return $this->namespaces;
	}

	//*******************************************************************

	/**
	 * Set classes
	 *
	 * @param string $classes
	 * @return Package
	 */
	public function setClasses($classes) {
		$this->classes = $classes;
		return $this;
	}

	/**
	 * Get classes
	 *
	 * @return string
	 */
	public function getClasses() {
		return $this->classes;
	}

	//*******************************************************************

	/**
	 * Add codeTags
	 *
	 * @param \Tugel\TugelBundle\Entity\CodeTag $codeTags
	 * @return Package
	 */
	public function addCodeTag(\Tugel\TugelBundle\Entity\CodeTag $codeTags) {
		$this->codeTags[] = $codeTags;
		return $this;
	}

	/**
	 * Remove codeTag
	 *
	 * @param \Tugel\TugelBundle\Entity\CodeTag $codeTag
	 */
	public function removeCodeTag(\Tugel\TugelBundle\Entity\CodeTag $codeTag) {
		$this->codeTags->removeElement($codeTag);
	}

	/**
	 * Get codeTags
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getCodeTags() {
		return $this->codeTags;
	}

	//*******************************************************************

	/**
	 * Set codeTagsMaximum
	 *
	 * @param integer $codeTagsMaximum
	 * @return Package
	 */
	public function setCodeTagsMaximum($codeTagsMaximum) {
		$this->codeTagsMaximum = $codeTagsMaximum;
		return $this;
	}

	/**
	 * Get codeTagsMaximum
	 *
	 * @return integer
	 */
	public function getCodeTagsMaximum() {
		return $this->codeTagsMaximum;
	}

	//*******************************************************************

	/**
	 * Set languages
	 *
	 * @param string $languages
	 * @return Package
	 */
	public function setLanguages($languages) {
		$this->languages = $languages;
		return $this;
	}

	/**
	 * Get languages
	 *
	 * @return string
	 */
	public function getLanguages() {
		return $this->languages;
	}

	//*******************************************************************

	/**
	 * Set codeTagsText
	 *
	 * @param string $codeTagsText
	 * @return Package
	 */
	public function setCodeTagsText($codeTagsText) {
		$this->codeTagsText = $codeTagsText;
		return $this;
	}

	/**
	 * Get codeTagsText
	 *
	 * @return string
	 */
	public function getCodeTagsText() {
		return $this->codeTagsText;
	}

	//*******************************************************************

	/**
	 * Add dependencies
	 *
	 * @param \Tugel\TugelBundle\Entity\Package $dependencies
	 * @return Package
	 */
	public function addDependency(\Tugel\TugelBundle\Entity\Package $dependencies) {
		$this->dependencies[] = $dependencies;
		return $this;
	}

	/**
	 * Remove dependencies
	 *
	 * @param \Tugel\TugelBundle\Entity\Package $dependencies
	 */
	public function removeDependency(\Tugel\TugelBundle\Entity\Package $dependencies) {
		$this->dependencies->removeElement($dependencies);
	}

	/**
	 * Get dependencies
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getDependencies() {
		return $this->dependencies;
	}

	//*******************************************************************

	/**
	 * Add dependencyOf
	 *
	 * @param \Tugel\TugelBundle\Entity\Package $dependencyOf
	 * @return Package
	 */
	public function addDependencyOf(\Tugel\TugelBundle\Entity\Package $dependencyOf) {
		$this->dependencyOf[] = $dependencyOf;
		return $this;
	}

	/**
	 * Remove dependencyOf
	 *
	 * @param \Tugel\TugelBundle\Entity\Package $dependencyOf
	 */
	public function removeDependencyOf(\Tugel\TugelBundle\Entity\Package $dependencyOf) {
		$this->dependencyOf->removeElement($dependencyOf);
	}

	/**
	 * Get dependencyOf
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getDependencyOf() {
		return $this->dependencyOf;
	}

	//*******************************************************************

	/**
	 * Set license
	 *
	 * @param string $license
	 * @return Package
	 */
	public function setLicense($license) {
		$this->license = $license;
		return $this;
	}

	/**
	 * Get license
	 *
	 * @return string
	 */
	public function getLicense() {
		return $this->license;
	}

	//*******************************************************************

}
