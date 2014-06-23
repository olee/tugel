<?php

namespace Zeutzheim\PpintBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * ZeutzheimPpintBundle:Language
 *
 * @ORM\Entity
 * @ORM\Table(name="language")
 */
class Language {
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
	 * @ORM\Column(name="ext", type="string", length=8, nullable=false)
	 */
	private $extension;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\ManyToMany(targetEntity="Platform", mappedBy="languages")
	 */
	private $platforms;

	//*******************************************************************

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->packages = new \Doctrine\Common\Collections\ArrayCollection();
		$this->platforms = new \Doctrine\Common\Collections\ArrayCollection();
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
	 * Set url
	 *
	 * @param string $url
	 * @return Platform
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
	 * Set extension
	 *
	 * @param string $extension
	 * @return Language
	 */
	public function setExtension($extension) {
		$this->extension = $extension;
		return $this;
	}

	/**
	 * Get extension
	 *
	 * @return string
	 */
	public function getExtension() {
		return $this->extension;
	}

	//*******************************************************************

	/**
	 * Add platforms
	 *
	 * @param \Zeutzheim\PpintBundle\Entity\Platform $platforms
	 * @return Language
	 */
	public function addPlatform(\Zeutzheim\PpintBundle\Entity\Platform $platforms) {
		$this->platforms[] = $platforms;
		return $this;
	}

	/**
	 * Remove platforms
	 *
	 * @param \Zeutzheim\PpintBundle\Entity\Platform $platforms
	 */
	public function removePlatform(\Zeutzheim\PpintBundle\Entity\Platform $platforms) {
		$this->platforms->removeElement($platforms);
	}

	/**
	 * Get platforms
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getPlatforms() {
		return $this->platforms;
	}

	//*******************************************************************

}
