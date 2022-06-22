<?php

namespace App\Controller;

use App\Entity\Video;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Category;
use App\Utils\CategoryTreeFrontPage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use App\Form\UserType;

class FrontController extends AbstractController
{
//    public function __construct( ManagerRegistry $doctrine) {}


    /**
     * @Route("/", name ="main_page")
     */
    public function index(): Response
    {
        return $this->render('front/index.html.twig');
    }



    /**
     * @Route("/video-list/category/{categoryname},{id}/{page}", defaults={"page": "1"}, name="video_list")
     */
    public function videoList($id,$page, CategoryTreeFrontPage $categories, ManagerRegistry $doctrine)
    {
        $ids = $categories->getChildIds($id);
        array_push($ids, $id);

        $videos = $doctrine
            ->getRepository(Video::class)
            ->findByChildIds($ids ,$page);

        $categories->getCategoryListAndParent($id);
        return $this->render('front/video_list.html.twig',[
            'subcategories' => $categories,
            'videos'=>$videos
        ]);
    }


    /**
     * @Route("/video-details", name ="video_details")
     */
    public function videoDetails(): Response
    {
        return $this->render('front/video_details.html.twig');
    }


    /**
     * @Route("/search-results", name ="search_results", methods={"POST"})
     */
    public function searchResults(): Response
    {
        return $this->render('front/search_results.html.twig');
    }


    /**
     * @Route("/pricing", name ="pricing")
     */
    public function pricing(): Response
    {
        return $this->render('front/pricing.html.twig');
    }




    /**
     * @Route("/register", name ="register")
     */
    public function register(UserPasswordEncoderInterface $password_encoder, Request $request)
    {
        $user = new User;
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();

            $user->setName($request->request->get('user')['name']);
            $user->setLastName($request->request->get('user')['last_name']);
            $user->setEmail($request->request->get('user')['email']);
            $password = $password_encoder->encodePassword($user, $request->request->get('user')['password']['first']);
            $user->setPassword($password);
            $user->setRoles(['ROLE_USER']);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->loginUserAutomatically($user, $password);

            return $this->redirectToRoute('admin_main_page');
        }
        return $this->render('front/register.html.twig',['form'=>$form->createView()]);
    }



    /**
     * @Route("/login", name="login")
     */
    public function login(AuthenticationUtils $helper)
    {
        return $this->render('front/login.html.twig', [
            'error' => $helper->getLastAuthenticationError()
        ]);
    }

    public function loginUserAutomatically($user, $password) {
        $token = new UsernamePasswordToken(
            $user,
            $password,
            'main', // security.yaml
            $user->getRoles()
        );
        $this->get('security.token_storage')->setToken($token);
        $this->get('session')->set('_security_main',serialize($token));
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout() : void
    {
        throw new \Exception('This should never be reached!');
    }


    /**
     * @Route("/payment", name ="payment")
     */
    public function payment(): Response
    {
        return $this->render('front/payment.html.twig');
    }


    public function mainCategories(ManagerRegistry $doctrine) {
        $categories = $doctrine
            ->getRepository(Category::class)
            -> findBy(['parent' => null], ['name' => 'ASC']);
        return $this->render('front/_main_categories.html.twig', [
            'categories' => $categories,
        ]);
    }
}