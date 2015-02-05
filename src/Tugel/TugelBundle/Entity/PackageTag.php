<?php

namespace Tugel\TugelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * TugelBundle:PackageTag
 *
 * @ORM\Entity
 * @ORM\Table(
 * 		name = "package_tag",
 * 		indexes = { 
 * 			@ORM\Index(name = "package_id", columns = {"package_id"}),
 * 			@ORM\Index(name = "tag_id", columns = {"tag_id"})
 * 		}
 * )
 */
class PackageTag {

	/**
	 * @var string
	 *
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Package", inversedBy="tags", cascade={"all"}, fetch="EXTRA_LAZY")
	 * @ORM\JoinColumn(name="package_id", nullable=false)
	 */
	private $package;

	/**
	 * @var string
	 *
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Tag", inversedBy="packageTags", cascade={"all"}, fetch="EAGER")
	 * @ORM\JoinColumn(name="tag_id", nullable=false)
	 */
	private $tag;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="count", type="integer", nullable=false)
	 */
	private $count;

	/*******************************************************************/

	public function __construct(\Tugel\TugelBundle\Entity\Package $package, \Tugel\TugelBundle\Entity\Tag $tag, $count = 1) {
		$this->package = $package;
		$this->tag = $tag;
		$this->count = $count;
	}

	/**
	 * Set name
	 *
	 * @param string $name
	 * @return PackageTag
	 */
	public function setName($name) {
		$this->tag->setName($name);
		return $this;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->tag->getName();
	}

	/*******************************************************************/
	
	/**
	 * Get id
	 *
	 * @return string
	 */
	public function getId() {
		return $this->package->getId() . '-' . $this->tag->getId();
	}

	/**
	 * Set package
	 *
	 * @param \Tugel\TugelBundle\Entity\Package $package
	 * @return PackageTag
	 */
	public function setPackage(\Tugel\TugelBundle\Entity\Package $package) {
		$this->package = $package;
		return $this;
	}

	/**
	 * Get package
	 *
	 * @return \Tugel\TugelBundle\Entity\Package
	 */
	public function getPackage() {
		return $this->package;
	}

	/**
	 * Set tag
	 *
	 * @param \Tugel\TugelBundle\Entity\Tag $tag
	 * @return PackageTag
	 */
	public function setTag(\Tugel\TugelBundle\Entity\Tag $tag) {
		$this->tag = $tag;
		return $this;
	}

	/**
	 * Get tag
	 *
	 * @return \Tugel\TugelBundle\Entity\Tag
	 */
	public function getTag() {
		return $this->tag;
	}

	/**
	 * Set count
	 *
	 * @param integer $count
	 * @return PackageTag
	 */
	public function setCount($count) {
		$this->count = $count;
		return $this;
	}

	/**
	 * Get count
	 *
	 * @return integer
	 */
	public function getCount() {
		return $this->count;
	}

}
