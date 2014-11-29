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
	 * @ORM\Column(name="name", type="string", nullable=false)
	 */
	private $name;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="namespaces", type="text", nullable=true)
	 */
	private $namespaces;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="classes", type="text", nullable=true)
	 */
	private $classes;

	/**
	 * @var \Zeutzheim\PpintBundle\Entity\CodeTag
	 *
	 * @ORM\OneToMany(targetEntity="CodeTag", mappedBy="version", fetch="EXTRA_LAZY", cascade={"all"}, orphanRemoval=true)
     * @ORM\OrderBy({"count" = "DESC"})
	 */
	private $codeTags;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="languages", type="string", length=1024, nullable=true)
	 */
	private $languages;

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
	 * @ORM\Column(name="added_date", type="datetime")
	 */
	private $addedDate;

	//*******************************************************************

	public function __construct() {
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
	 * Set namespaces
	 *
	 * @param string $namespaces
	 * @return Version
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
	 * @return Version
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
     * @param \Zeutzheim\PpintBundle\Entity\CodeTag $codeTags
     * @return Version
     */
    public function addCodeTag(\Zeutzheim\PpintBundle\Entity\CodeTag $codeTags)
    {
        $this->codeTags[] = $codeTags;
        return $this;
    }

    /**
     * Remove codeTags
     *
     * @param \Zeutzheim\PpintBundle\Entity\CodeTag $codeTags
     */
    public function removeCodeTag(\Zeutzheim\PpintBundle\Entity\CodeTag $codeTags)
    {
        $this->codeTags->removeElement($codeTags);
    }

    /**
     * Get codeTags
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCodeTags()
    {
        return $this->codeTags;
    }
	
	//*******************************************************************

	/**
	 * Set languages
	 *
	 * @param string $languages
	 * @return Version
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
	 * Set indexed
	 *
	 * @param boolean $indexed
	 * @return Version
	 */
	public function setIndexed($indexed) {
		$this->indexed = $indexed;
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
     * Get indexed
     *
     * @return boolean 
     */
    public function getIndexed()
    {
        return $this->indexed;
    }

    /**
     * Get error
     *
     * @return boolean 
     */
    public function getError()
    {
        return $this->error;
    }

}
