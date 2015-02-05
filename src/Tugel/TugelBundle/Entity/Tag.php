<?php

namespace Tugel\TugelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * TugelBundle:Tag
 *
 * @ORM\Entity
 * @ORM\Table(
 * 		name="tag",
 * 		uniqueConstraints = { 
 * 			@ORM\UniqueConstraint(name = "name", columns = {"name"}),
 * 		}
 * )
 */
class Tag {

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
	 * @ORM\Column(name="name", type="string", length=127, nullable=false)
	 */
	private $name;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\OneToMany(targetEntity="PackageTag", mappedBy="tag", fetch="EXTRA_LAZY", cascade={"all"}, orphanRemoval=true)
	 * @JMS\Exclude
	 */
	private $packageTags;

	/*******************************************************************/

	public function __construct($name) {
		$this->name = $name;
		$this->packageTags = new \Doctrine\Common\Collections\ArrayCollection();
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
	 * Set name
	 *
	 * @param string $name
	 * @return PackageTag
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

	/**
	 * Add packageTags
	 *
	 * @param \Tugel\TugelBundle\Entity\PackageTag $packageTags
	 * @return Tag
	 */
	public function addPackageTag(\Tugel\TugelBundle\Entity\PackageTag $packageTags) {
		$this->packageTags[] = $packageTags;
		return $this;
	}

	/**
	 * Remove packageTags
	 *
	 * @param \Tugel\TugelBundle\Entity\PackageTag $packageTags
	 */
	public function removePackageTag(\Tugel\TugelBundle\Entity\PackageTag $packageTags) {
		$this->packageTags->removeElement($packageTags);
	}

	/**
	 * Get packageTags
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getPackageTags() {
		return $this->packageTags;
	}

}
