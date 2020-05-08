<?php

namespace App\Controller;

use App\Entity\Turno;
use App\Entity\TurnoRechazado;
use App\Repository\TurnoRepository;
use App\Repository\OficinaRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class EstadisticaController extends AbstractController
{
    /**
     * @Route("/estadistica", name="estadistica_index", methods={"GET", "POST"})
     */
    public function index(OficinaRepository $oficinaRepository): Response
    {
        return $this->render('estadistica/index.html.twig', [
            'oficinas' => $oficinaRepository->findAllWithUltimoTurno(),
        ]);
    }
}
