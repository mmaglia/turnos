<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    //ORIGINAL Route("/", name="main")
    /**
     * @Route("/TurnosWeb", name="mainTMP")
     */
    public function index()
    {
        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
        ]);
    }

    /**
     * @Route("/", name="main")
     */
    public function homeTMP()
    {
        return new Response("");
    }

}
