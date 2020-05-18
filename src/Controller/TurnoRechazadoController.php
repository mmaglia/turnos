<?php

namespace App\Controller;

use App\Entity\TurnoRechazado;
use App\Form\TurnoRechazadoType;
use App\Repository\TurnoRechazadoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/rechazados")
 */
class TurnoRechazadoController extends AbstractController
{
    /**
     * @Route("/", name="turno_rechazado_index", methods={"GET"})
     */
    public function index(TurnoRechazadoRepository $turnoRechazadoRepository): Response
    {

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_AUDITORIA_GESTION')) {
            $turnos_rechazados = $turnoRechazadoRepository->findAllOrderedByColum('fechaHoraTurno');
        } else {
            if ($this->isGranted('ROLE_USER')) {
                // Busca los rechazads correspondientes a la oficina del usuario logueado
                $oficinaUsuario = $this->getUser()->getOficina();
                $turnos_rechazados = $turnoRechazadoRepository->findAllOrderedByColum('fechaHoraTurno', null, $oficinaUsuario);
            }
        }

        return $this->render('turno_rechazado/index.html.twig', [
            'turno_rechazados' => $turnos_rechazados
        ]);
    }

    /**
     * @Route("/new", name="turno_rechazado_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

        $turnoRechazado = new TurnoRechazado();
        $form = $this->createForm(TurnoRechazadoType::class, $turnoRechazado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($turnoRechazado);
            $entityManager->flush();

            return $this->redirectToRoute('turno_rechazado_index');
        }

        return $this->render('turno_rechazado/new.html.twig', [
            'turno_rechazado' => $turnoRechazado,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="turno_rechazado_show", methods={"GET"})
     */
    public function show(TurnoRechazado $turnoRechazado): Response
    {
        return $this->render('turno_rechazado/show.html.twig', [
            'turno_rechazado' => $turnoRechazado,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="turno_rechazado_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, TurnoRechazado $turnoRechazado): Response
    {
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

        $form = $this->createForm(TurnoRechazadoType::class, $turnoRechazado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('turno_rechazado_index');
        }

        return $this->render('turno_rechazado/edit.html.twig', [
            'turno_rechazado' => $turnoRechazado,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="turno_rechazado_delete", methods={"DELETE"})
     */
    public function delete(Request $request, TurnoRechazado $turnoRechazado): Response
    {
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

        if ($this->isCsrfTokenValid('delete'.$turnoRechazado->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($turnoRechazado);
            $entityManager->flush();
        }

        return $this->redirectToRoute('turno_rechazado_index');
    }
}
