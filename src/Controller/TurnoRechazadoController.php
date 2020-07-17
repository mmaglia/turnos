<?php

namespace App\Controller;

use App\DataTables\TurnoRechazadoTableType;
use App\Entity\TurnoRechazado;
use App\Form\TurnoRechazadoType;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/rechazados")
 */
class TurnoRechazadoController extends AbstractController
{
    /**
     * Variable auxiliar para crear datatables
     *
     * @var [DataTableFactory]
     */
    protected $datatableFactory;

    public function __construct(DataTableFactory $datatableFactory)
    {
        $this->datatableFactory = $datatableFactory;
    }
    /**
     * @Route("/", name="turno_rechazado_index")
     */
    public function index(Request $request): Response
    {
        // Busca los rechazados correspondientes a la oficina del usuario logueado
        $oficinaId = null;
        if ($this->isGranted('ROLE_USER') && !is_null($this->getUser()->getOficina())) {
            $oficinaId = $this->getUser()->getOficina()->getId();   // Obtengo Id de la Oficina asociada al Usuario
        }

        $table = $this->datatableFactory->createFromType(TurnoRechazadoTableType::class, is_null($oficinaId) ? array() : array($oficinaId))->handleRequest($request);
        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('turno_rechazado/index.html.twig', ['datatable' => $table]);
    }

    /**
     * @Route("/new", name="turno_rechazado_new", methods={"GET","POST"})
     * 
     * @IsGranted("ROLE_EDITOR")
     */
    public function new(Request $request): Response
    {
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
     * 
     * @IsGranted("ROLE_EDITOR")
     */
    public function edit(Request $request, TurnoRechazado $turnoRechazado): Response
    {
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
     * 
     * @IsGranted("ROLE_EDITOR")
     */
    public function delete(Request $request, TurnoRechazado $turnoRechazado): Response
    {
        if ($this->isCsrfTokenValid('delete'.$turnoRechazado->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($turnoRechazado);
            $entityManager->flush();
        }

        return $this->redirectToRoute('turno_rechazado_index');
    }
}
