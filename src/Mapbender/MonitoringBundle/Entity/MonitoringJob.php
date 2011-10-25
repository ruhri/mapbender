<?php
namespace Mapbender\MonitoringBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Description of MonitoringDefinition
 * 
 * @author apour
 * @ORM\Entity
 */
class MonitoringJob {
	/**
	 *
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;
	
	/**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $timestamp;
	
	/**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $latency;
	
	/**
	 *
	 * @ORM\Column(type="string", nullable="true")
	 */
	protected $changed;

	/**
	 *
     * @ORM\ManyToOne(targetEntity="MonitoringDefinition")
	 */
	protected $monitoringDefinition;
}