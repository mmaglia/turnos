<?php

namespace App\Controller;

use App\Entity\Organismo;
use App\Form\OrganismoType;
use App\Repository\OrganismoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/organismo")
 */
class OrganismoController extends AbstractController
{
    /**
     * @Route("/", name="organismo_index", methods={"GET"})
     */
    public function index(OrganismoRepository $organismoRepository): Response
    {
        return $this->render('organismo/index.html.twig', [
            'organismos' => $organismoRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="organismo_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $organismo = new Organismo();
        $form = $this->createForm(OrganismoType::class, $organismo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($organismo);
            $entityManager->flush();

            return $this->redirectToRoute('organismo_index');
        }

        return $this->render('organismo/new.html.twig', [
            'organismo' => $organismo,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="organismo_show", methods={"GET"})
     */
    public function show(Organismo $organismo): Response
    {
        return $this->render('organismo/show.html.twig', [
            'organismo' => $organismo,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="organismo_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Organismo $organismo): Response
    {
        $form = $this->createForm(OrganismoType::class, $organismo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('organismo_index');
        }

        return $this->render('organismo/edit.html.twig', [
            'organismo' => $organismo,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="organismo_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Organismo $organismo): Response
    {
        if ($this->isCsrfTokenValid('delete'.$organismo->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($organismo);
            $entityManager->flush();
        }

        return $this->redirectToRoute('organismo_index');
    }
}
