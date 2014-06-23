<?php

namespace Zeutzheim\PpintBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * ZeutzheimPpintBundle:Package
 *
 * @ORM\Entity
 * @ORM\Table(name="package")
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
	 * @ORM\OneToMany(targetEntity="Version", mappedBy="package")
	 * @JMS\Exclude
	 */
	private $versions;

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
	
}
