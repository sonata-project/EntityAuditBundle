<?php
namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity()
 */
class Issue198Owner
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;
    
    /**
     * @ORM\OneToMany(targetEntity="Issue198Car", mappedBy="owner")
     */
    protected $cars;
    
    public function __construct()
    {
        $this->cars = new ArrayCollection();
    }
    
    public function setId($id)
    {
        $this->id = $id;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function addCar(Issue198Car $car)
    {
        if (!$this->cars->contains($car)) {
            $car->setOwner($this);
            $this->cars[] = $car;
        }
    }
    
    public function removeCar(Issue198Car $car)
    {
        $this->cars->removeElement($car);
    }
    
    public function getCars()
    {
        return $this->cars;
    }
}
