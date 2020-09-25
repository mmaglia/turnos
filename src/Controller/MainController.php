<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;



class MainController extends AbstractController
{
    /**
     * @Route("/", name="main")
     */
    public function index(ConfigRepository $configRepository, SessionInterface $session)
    {

//        $textoPortada = $configRepository->findByClave('Portada')->getHtml();

        // Limpio variables de session que pueden haberse utilizado en la obtención de un turno anterior
        $session->remove('turno');
        $session->remove('persona');


        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
//            'textoPortada' => $textoPortada
        ]);
    }
}
