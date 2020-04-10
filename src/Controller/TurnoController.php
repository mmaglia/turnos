<?php

namespace App\Controller;

use App\Entity\Turno;
use App\Form\TurnoType;
use App\Repository\TurnoRepository;
use App\Entity\Oficina;
use App\Repository\LocalidadRepository;
use App\Repository\OficinaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/turno")
 */
class TurnoController extends AbstractController
{
    /**
     * @Route("/", name="turno_index", methods={"GET"})
     */
    public function index(TurnoRepository $turnoRepository): Response
    {
        return $this->render('turno/index.html.twig', [
            'turnos' => $turnoRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="turno_new", methods={"GET","POST"})
     */
    public function new(Request $request, LocalidadRepository $localidadRepository): Response
    {
        $turno = new Turno();
        $form = $this->createForm(TurnoType::class, $turno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($turno);
            $entityManager->flush();

            return $this->redirectToRoute('turno_index');
            */
        }

        return $this->render('turno/new.html.twig', [
            'turno' => $turno,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="turno_show", methods={"GET"})
     */
    public function show(Turno $turno): Response
    {
        return $this->render('turno/show.html.twig', [
            'turno' => $turno,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="turno_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Turno $turno): Response
    {
        $form = $this->createForm(TurnoType::class, $turno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('turno_index');
        }

        return $this->render('turno/edit.html.twig', [
            'turno' => $turno,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="turno_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Turno $turno): Response
    {
        if ($this->isCsrfTokenValid('delete'.$turno->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($turno);
            $entityManager->flush();
        }

        return $this->redirectToRoute('turno_index');
    }


    /**
     * @Route("/oficina_localidad", name="oficinas_by_localidad", condition="request.headers.get('X-Requested-With') == 'XMLHttRequest'")
     */
    /*
    public function oficinasByLocalidad(Request $request, OficinaRepository $oficinaRepository) {

//        $em = $this->getDoctrine()->getManager();
        $localidad_id = $request->request->get('localidad_id');
        $oficinas = $oficinaRepository->findOficinaByLocalidad($localidad_id);

        return new JsonResponse($oficinas);

    }
    */
}
