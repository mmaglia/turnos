<?php

namespace App\Controller;

use App\DataTables\ConfigTableType;
use App\Entity\Config;
use App\Form\ConfigType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Omines\DataTablesBundle\DataTableFactory;

/**
 * @Route("/config")
 */
class ConfigController extends AbstractController
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
     * @Route("/", name="config_index")
     */
    public function index(Request $request): Response
    {
        $table = $this->datatableFactory->createFromType(ConfigTableType::class, array())->handleRequest($request);
        if ($table->isCallback()) {
            return $table->getResponse();
        }
        return $this->render('config/index.html.twig', ['datatable' => $table]);
    }

    /**
     * @Route("/new", name="config_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $config = new Config();
        $form = $this->createForm(ConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($config);
            $entityManager->flush();

            return $this->redirectToRoute('config_index');
        }

        return $this->render('config/new.html.twig', [
            'config' => $config,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="config_show", methods={"GET"})
     */
    public function show(Config $config): Response
    {
        return $this->render('config/show.html.twig', [
            'config' => $config,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="config_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Config $config): Response
    {
        $form = $this->createForm(ConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('config_index');
        }

        return $this->render('config/edit.html.twig', [
            'config' => $config,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="config_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Config $config): Response
    {
        if ($this->isCsrfTokenValid('delete'.$config->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($config);
            $entityManager->flush();
        }

        return $this->redirectToRoute('config_index');
    }
}
