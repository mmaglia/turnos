<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ConfigRepository;


class MainController extends AbstractController
{
    /**
     * @Route("/", name="main")
     */
    public function index(ConfigRepository $configRepository)
    {

//        $textoPortada = $configRepository->findByClave('Portada')->getHtml();

        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
//            'textoPortada' => $textoPortada
        ]);
    }
}
