<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use AppBundle\Entity\User;
use AppBundle\Entity\TimeSlot;
use Datetime;

use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;

class UserController extends FOSRestController
{
    /**
    * @Rest\Get("/users/")
    *
    * Lists all the users.
    *
    * @SWG\Response(
    *   response=200,
    *   description="List of users",
    *   @SWG\Schema(
    *     type="array",
    *      @Model(type=User::class)
    *     )
    *   )
    * )
    * @SWG\Tag(name="Users")
    */
     public function getUsers()
     {
       $users = $this->getDoctrine()->getRepository('AppBundle:User')->findAll();
         if ($users === null) {
           return new View("there are no users", Response::HTTP_NOT_FOUND);
      }
         return $users;
     }

    /**
    * @Rest\Post("/users/")
    *
    *   Deals the POST request to insert a new user.   
    *
    * @SWG\Response(
    *     response=200,
    *     description="The created user",
    *     @Model(type=User::class)
    * )
    * @SWG\Response(
    *     response=404,
    *     description="NULL VALUES ARE NOT ALLOWED",
    * )
    *   @SWG\Parameter(
     *     in="body",
     *     description="User to be inserted",
     *     required=true,
    *      name="user",
     *     type="object",
    *      @SWG\Schema(
    *           type="object",
    *          @SWG\Property(property="name", type="string", description="name of the user"),
    *          @SWG\Property(property="role", type="string", description="role of the user (interviewer or candidate")
     *     )
     *   ),
    * @SWG\Tag(name="Users")
    */
    public function postUser(Request $request)
    {        
        $requestData = json_decode($request->getContent(), true);
        $name = $requestData["name"];
        $role = $requestData["role"];

        if(empty($name) || empty($role))
        {
            return new View("EMPTY VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE); 
        } 
        $user = new User;
        $user->setName($name);
        $user->setRole($role);
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        return new View($user, Response::HTTP_OK);
    }

    /**
    * @Rest\Get("/users/{id}")
    *
    * @SWG\Response(
    *     response=200,
    *     description="The selected user",
    *     @Model(type=User::class)
    * )
    * @SWG\Response(
    *     response=404,
    *     description="The user does not exists",
    * )
    * @SWG\Tag(name="Users")
    */
    public function getUserById($id)
    {
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->find($id);
        if ($user === null) {
            return new View("user not found", Response::HTTP_NOT_FOUND);
        }
        return $user;
    }

    /**
    * @Rest\Get("/users/{id}/timeslots")
    * @SWG\Response(
    *   response=200,
    *   description="List of user's timeslots",
    *   @SWG\Schema(
    *     type="array",
    *      @Model(type=TimeSlot::class)
    *     )
    *   )
    * )
    * @SWG\Tag(name="Users")
    */
    public function getTimeslotsByUserId(Request $request, $id){
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->find($id);
        if (empty($user)) {
            return new View("user not found", Response::HTTP_NOT_FOUND);
        } 
        return $user->getTimeslots();
    }

    /**
    * @Rest\Post("/user/{id}/timeslots")
    *
    *   Deals the POST request to insert timeslots to a user.   
    *
    * @SWG\Response(
    *     response=200,
    *     description="The timeslots were inserted"
    * )
    * @SWG\Response(
    *     response=404,
    *     description="If the user does not exists",
    * )
    *   @SWG\Parameter(
     *     in="body",
     *     description="timeslots to be inserted",
     *     required=true,
    *      name="timeslots",
     *     type="object",
    *      @SWG\Schema(
    *            type="array",
    *            @SWG\Items(
    *              type="object",
    *              @SWG\Property(property="start_time", type="integer", description="Epoch timestamp"),
    *              @SWG\Property(property="end_time", type="integer", description="Epoch timestamp")
    *          )
     *     )
     *   ),
    * @SWG\Tag(name="Users")
    */

    public function postTimeslotsByUserId(Request $request, $id)
    {
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->find($id);
        if ($user === null) {
            return new View("User not found", Response::HTTP_NOT_FOUND);
        }

        $requestData = json_decode($request->getContent(), true);
        $timeslots = $requestData["timeslots"];
        if(empty($timeslots))
        {
            return new View("No timeslots were given", Response::HTTP_NOT_ACCEPTABLE); 
        } 

        foreach ($timeslots as $key => $timeslot) {
            $isValidStartTime = $this->isValidTimeStamp($timeslot["start_time"]);
            $isValidEndTime = $this->isValidTimeStamp($timeslot["end_time"]);

            if(!$isValidStartTime || !$isValidEndTime){
                return new View("Please give me valid timestamps", Response::HTTP_NOT_ACCEPTABLE); 
            }

            $startTimestamp = $timeslot["start_time"];
            $endTimestamp = $timeslot["end_time"];

            //if one of the timestamps does not have a rounded hour
            if ( ($startTimestamp % 3600 != 0) || ($endTimestamp % 3600 != 0) ){
                return new View("Each time must be round", Response::HTTP_NOT_ACCEPTABLE); 
            }

            $hoursDifference = $this->hoursTimeDifference($startTimestamp, $endTimestamp);
            if($hoursDifference >= 1){
                //For each hour of diference, insert a time slot in DB of one hour
                for ($i=0; $i < $hoursDifference; $i++) { 
                    $startTime = date("r", $startTimestamp + 3600 * $i );
                    $endTime = date("r", $startTimestamp-1 +  3600 * ($i+1) );
                    
                    $startTimeDT = new DateTime($startTime);  // convert UNIX timestamp to PHP DateTime      
                    $endTimeDT = new DateTime($endTime);  // convert UNIX timestamp to PHP DateTime     

                    $data = new TimeSlot;
                    $data->setStartTime($startTimeDT);
                    $data->setEndTime($endTimeDT);
                    $data->setUser($user);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($data);
                    $em->flush();
                }
            }

        }
        return new View("Timeslots added sucessfully", Response::HTTP_OK);
    }

    /**
    * @Rest\Put("/users/{id}")
    *
    *   Deals the PUT request to edit one user
    *
    * @SWG\Response(
    *     response=200,
    *     description="Updated user",
    *     @Model(type=User::class)
    * )
    * @SWG\Response(
    *     response=404,
    *     description="User not not found",
    * )
    * @SWG\Parameter(
     *     name="name",
     *     in="query",
     *     type="string",
     *     description="The user name"
     * )
    * @SWG\Parameter(
     *     name="role",
     *     in="query",
     *     type="string",
     *     description="The user role (interviewer or candidate)"
     * )
    * @SWG\Tag(name="Users")
    */
    public function updateUserbyId($id,Request $request)
    { 
        $name = $request->get('name');
        $role = $request->get('role');
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->find($id);
        $sn = $this->getDoctrine()->getManager();
        if (empty($user)) {
            return new View("user not found", Response::HTTP_NOT_FOUND);
        } 
        elseif(!empty($name) && !empty($role)){
            $user->setName($name);
            $user->setRole($role);
            $sn->flush();
            return new View($user, Response::HTTP_OK);
        }
        elseif(empty($name) && !empty($role)){
            $user->setRole($role);
            $sn->flush();
            return new View($user, Response::HTTP_OK);
        }
        elseif(!empty($name) && empty($role)){
            $user->setName($name);
            $sn->flush();
            return new View($user, Response::HTTP_OK); 
        }
        else return new View("User name or role cannot be empty", Response::HTTP_NOT_ACCEPTABLE); 
    }
    
    /**
    * @Rest\Delete("/users/{id}")
    *
    *   Deals with the DELETE request to remove a user by id.
    *
    * @SWG\Response(
    *     response=200,
    *     description="deleted successfully"
    * )
    * @SWG\Response(
    *     response=404,
    *     description="user not found",
    * )
    * @SWG\Tag(name="Users")
    */
    public function deleteUserById($id)
    {
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->find($id);
        if (empty($user)) {
            return new View("user not found", Response::HTTP_NOT_FOUND);
        }
        else {
            $manager = $this->getDoctrine()->getManager();
            $manager->remove($user);
            $manager->flush();
        }
        return new View("deleted successfully", Response::HTTP_OK);
    }

    /**
    * @Rest\Get("/availableTimeslots")
    *
    * @SWG\Response(
    *     response=200,
    *     description="The available timeslots for that candidate to be interviewed with each interviewer",
    *   @SWG\Schema(
    *       type="array",
    *       @Model(type=User::class)
    *     )
    * )
    * @SWG\Response(
    *     response=404,
    *     description="If the user is not found or it is not a candidate",
    * )
    * @SWG\Parameter(
     *     name="interviewers",
     *     in="query",
     *     type="string",
     *     description="The Interviewer ids separated by comma (,)"
     * )
        * @SWG\Parameter(
     *     name="candidate_id",
     *     in="query",
     *     type="integer",
     *     description="The candidate id"
     * )
    * @SWG\Tag(name="Users")
    */
    public function getAvailableTimeslots(Request $request)
    {
        $interviewers = $request->query->get('interviewers');
        $interviewersArray = explode(',', $interviewers);
        
        $candidateId = $request->query->get('candidate_id');
        $candidate = $this->getDoctrine()->getRepository('AppBundle:User')->find($candidateId);
        
        if ($candidate === null) {
            return new View("User not found", Response::HTTP_NOT_FOUND);
        }
        if($candidate->getRole() != 'candidate') {
            return new View("That is not a candidate", Response::HTTP_NOT_FOUND);
        }

        //GET corresponding timeslots in the given interval for interviewers
        $qb = $this->getDoctrine()->getRepository('AppBundle:User')->createQueryBuilder('u');
        return $qb->select('u, t')
            ->join('u.timeslots', 't')
            ->where("u.role != :role")
            ->setParameter('role', 'candidate')
            ->andWhere('u.id = t.user')
            ->andWhere("u.id IN(:interviewers)")
            ->setParameter('interviewers', $interviewersArray)
            ->andWhere($qb->expr()->gte('t.startTime', "ANY (SELECT	time.startTime FROM  AppBundle:TimeSlot time WHERE time.user = :candidate_id)"))
            ->andWhere($qb->expr()->lte('t.endTime', "ANY (SELECT time2.endTime FROM  AppBundle:TimeSlot time2 WHERE time2.user = :candidate_id)"))
            ->setParameter('candidate_id', $candidate->getId())
            ->getQuery()
            ->getResult();
    }

    //HELPER FUNCTIONS
    /**
    * Checks if the given timestamp is valid
    */
    function isValidTimeStamp($timestamp)
    {
        return ((string) (int) $timestamp === $timestamp) 
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
    }

    /**
    * Calculates the time diference between two epoch timestamps
    */
    function hoursTimeDifference($startTimestamp, $endTimestamp){
        $startTime = date("r", $startTimestamp);
        $endTime = date("r", $endTimestamp);
        $ts1 = strtotime($startTime);
        $ts2 = strtotime($endTime);     
        $seconds_diff = $ts2 - $ts1;                            
        return ($seconds_diff/3600);
    }
}