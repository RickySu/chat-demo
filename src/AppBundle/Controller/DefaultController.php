<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity;

class DefaultController extends Controller
{

    /**
     * @Route("/", name="homepage")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $profile = $request->getSession()->get('profile');
        if(!$profile){
            return $this->redirectToRoute('register');
        }

        $this->sendBrocastMessage(array(
            'type' => 'joined',
            'from' => $profile,
            'time' => floor(microtime(true)*1000),
        ));

        $repository = $this->getDoctrine()->getRepository('AppBundle:Chat');
        $chats = $repository->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
        return array('profile' => $profile, 'chats' => $chats);
    }

    /**
     * @Route("/register", name="register")
     * @Template()
     */
    public function registerAction(Request $request)
    {
        return array();
    }

    /**
     * @Route("/register/check", name="register_check")
     */
    public function registerActionCheck(Request $request)
    {
        $email = $request->get('email');
        $nickname = $request->get('nickname');
        $request->getSession()->set('profile', array(
            'hash' => md5($email),
            'nickname' => $nickname,
        ));
        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/send")
     * @Method({"POST"})
     * @param Request $request
     */
    public function sendAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $message = htmlspecialchars($request->get('message'));
        $profile = $request->getSession()->get('profile');
        $id = floor(microtime(true)*1000);
        $chat = new Entity\Chat();
        $chat->setId($id);
        $chat->setNickname($profile['nickname']);
        $chat->setHash($profile['hash']);
        $chat->setMessage($message);
        $em->persist($chat);
        $em->flush();
        $this->sendBrocastMessage(array(
            'type' => 'update',
            'id' => $id,
            'from' => $profile,
            'msg' => $message,
        ));
        return new JsonResponse(array(
            'status' => true,
        ));
    }

    protected function sendBrocastMessage($message)
    {
        $jsonMessage = json_encode($message);
        $ch = curl_init('http://localhost:8080/brocast');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonMessage);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonMessage))
        );
        $result = curl_exec($ch);
    }

}
