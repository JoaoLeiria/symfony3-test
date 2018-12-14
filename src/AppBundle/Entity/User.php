<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 *
 */
class User
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SWG\Property(description="The unique identifier of the user.")
     */
     
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=255)
     */
    private $role;

    /**
     * @ORM\OneToMany(targetEntity="TimeSlot", mappedBy="user")
     */
     private $timeslots;

     public function __construct()
     {
         $this->timeslots = new ArrayCollection();
     }
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return User
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
     * Set role
     *
     * @param string $role
     *
     * @return User
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set Timeslots
     *
     * @return string
     */
     public function setTimeslots($timeslots)
     {
        $this->timeslots = $timeslots;
        return $this;
     }

    /**
     * Get Timeslots
     *
     * @return string
     */
     public function getTimeslots()
     {
         return $this->timeslots;
     }

     public function getAvailableTimeslots($start, $end)
     {
         dump($this);
         return $this->createQueryBuilder('user')
             // p.category refers to the "category" property on product
             ->innerJoin('user.timeslots', 'timeslots')
             // selects all the category data to avoid the query
            //  ->addSelect('c')
             ->where('user.id = :user_id')
             ->setParameter('user_id', 1)
             ->getQuery()
             ->getOneOrNullResult();
     }
}

