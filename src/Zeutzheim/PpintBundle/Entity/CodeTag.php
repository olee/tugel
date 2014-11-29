<?php

namespace Zeutzheim\PpintBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ZeutzheimPpintBundle:CodeTag
 *
 * @ORM\Table(name="codetag", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="version_idx", columns={"version_id", "name"})
 * })
 * @ORM\Entity
 */
class CodeTag
{

	/**
	 * @var string
	 *
     * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Version", cascade={"all"}, fetch="EXTRA_LAZY")
	 * @ORM\JoinColumn(name="version_id", nullable=false)
	 */
	private $version;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="name", type="string", length=64, nullable=false)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="count", type="integer", nullable=false)
     */
    private $count;

	public function __construct(\Zeutzheim\PpintBundle\Entity\Version $version, $name, $count = 1) {
		$this->version = $version;
		$this->name = $name;
		$this->count = $count;
	}

    /**
     * Get pseudo-id
     */
    public function getId()
    {
        return $this->version->getId() . ":" . $this->name;
    }

    /**
     * Set version
     *
     * @param \Zeutzheim\PpintBundle\Entity\Version $version
     * @return CodeTag
     */
    public function setVersion(\Zeutzheim\PpintBundle\Entity\Version $version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Get version
     *
     * @return \Zeutzheim\PpintBundle\Entity\Version 
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return CodeTag
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set count
     *
     * @param integer $count
     * @return CodeTag
     */
    public function setCount($count)
    {
        $this->count = $count;
    
        return $this;
    }

    /**
     * Get count
     *
     * @return integer 
     */
    public function getCount()
    {
        return $this->count;
    }

}
