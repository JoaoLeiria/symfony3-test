<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
/**
 * TimeSlot
 *
 * @ORM\Table(name="time_slot")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TimeSlotRepository")
 */
class TimeSlot
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="datetime")
     */
    private $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_time", type="datetime")
     */
    private $endTime;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="timeslots")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
     private $user;

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
     * Set User
     *
     * @return string
     */
     public function setUser($user)
     {
        $this->user = $user;
        return $this;
     }

    /**
     * Get user
     *
     * @return string
     */
     public function getUser()
     {
         return $this->user;
     }

    /**
     * Set startTime
     *
     * @param \DateTime $startTime
     *
     * @return TimeSlot
     */
    public function setStartTime(DateTime $startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return \DateTime
     */
    public function getStartTime()
    {
        return date_create_from_format('Y-m-d H:i:s', $this->startTime);
        return $this->startTime->format('Y-m-d H:i:s');
    }

    /**
     * Set endTime
     *
     * @param \DateTime $endTime
     *
     * @return TimeSlot
     */
    public function setEndTime(DateTime $endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return \DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }
}

