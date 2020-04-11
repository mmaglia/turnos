<?php

namespace App\Controller;

use App\Entity\Oficina;
use App\Form\OficinaType;
use App\Form\AddTurnosType;
use App\Repository\OficinaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Turno;
use App\Repository\TurnoRepository;

use DateTime;
use DateInterval;

/**
 * @Route("/oficina")
 */
class OficinaController extends AbstractController
{
    /**
     * @Route("/", name="oficina_index", methods={"GET"})
     */
    public function index(OficinaRepository $oficinaRepository): Response
    {
        return $this->render('oficina/index.html.twig', [
            'oficinas' => $oficinaRepository->findAllWithUltimoTurno(),
        ]);
    }

    /**
     * @Route("/new", name="oficina_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $oficina = new Oficina();
        $form = $this->createForm(OficinaType::class, $oficina);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($oficina);
            $entityManager->flush();

            return $this->redirectToRoute('oficina_index');
        }

        return $this->render('oficina/new.html.twig', [
            'oficina' => $oficina,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/addTurnos", name="oficina_addTurnos", methods={"GET","POST"})
     */
    public function addTurnos(Request $request, Oficina $oficina, TurnoRepository $turnoRepository): Response
    {

        $ultimoTurno = $turnoRepository->findUltimoTurnoByOficina($oficina);

        // Controla que existean turnos previos.
        if ($ultimoTurno) {
            $fechaHoraUltimoTurno = $ultimoTurno[0]->getFechaHora();
        } else { // Sino establece el día actual como punto de partida
            $fechaHoraUltimoTurno = new DateTime('now');
        }

        $frecuencia = $oficina->getFrecuenciaAtencion();
        $cantidadDias = 30;

        $form = $this->createForm(AddTurnosType::class, [
            'cantidadDias' => $cantidadDias,
            ] );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cantidadDias = $request->request->get('add_turnos')['cantidadDias'];

            $entityManager = $this->getDoctrine()->getManager();

            // Recorre cada día del intervalo indicado
            for ($i=1; $i <= $cantidadDias; $i++){

                // Incrementa fecha en un 1 día
                $nuevoTurno = $fechaHoraUltimoTurno->add(new DateInterval('P1D'));

                // Verifico que no sea sábado (6) o domingo (7)
                if ($nuevoTurno->format('N') >= 6) {
                    continue; // Salteo el día
                }

                // Establece la hora máxima para el día que se está generando
                $ultimoTurnoDelDia = new DateTime($nuevoTurno->format('Y-m-d H:i'));
                $ultimoTurnoDelDia = $ultimoTurnoDelDia->setTime($oficina->getHoraFinAtencion()->format('H'), $oficina->getHoraFinAtencion()->format('i'));

                // Establece la hora de Inicio de Atención
                $nuevoTurno = $nuevoTurno->setTime($oficina->getHoraInicioAtencion()->format('H'), $oficina->getHoraInicioAtencion()->format('i'));
                
                // Recorre intervalos para el día en proceso
                $j=0;
                while (true) {
                    $j++;

                    $nuevoTurno = $fechaHoraUltimoTurno->add(new DateInterval('PT' . $frecuencia . 'M'));

                    // Genera el alta del turno
                    $turno = new Turno();
                    $turno->setFechaHora($nuevoTurno);
                    $turno->setOficina($oficina);
                    $entityManager->persist($turno);       
                    $this->getDoctrine()->getManager()->flush();

                    if ($nuevoTurno > $ultimoTurnoDelDia) {
                        break;
                    }

                }               
            }

            // TODO ver de notificar la cantidad de turnos creados
            return $this->redirectToRoute('oficina_index');
        }

        return $this->render('oficina/add_turnos.html.twig', [
            'oficina' => $oficina,
            'fechaHoraUltimoTurno' => $fechaHoraUltimoTurno,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/{id}", name="oficina_show", methods={"GET"})
     */
    public function show(Oficina $oficina): Response
    {
        
        return $this->render('oficina/show.html.twig', [
            'oficina' => $oficina,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="oficina_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Oficina $oficina): Response
    {
        $form = $this->createForm(OficinaType::class, $oficina);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('oficina_index');
        }

        return $this->render('oficina/edit.html.twig', [
            'oficina' => $oficina,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="oficina_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Oficina $oficina): Response
    {
        if ($this->isCsrfTokenValid('delete'.$oficina->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($oficina);
            $entityManager->flush();
        }

        return $this->redirectToRoute('oficina_index');
    }
}
