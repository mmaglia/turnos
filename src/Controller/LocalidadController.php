<?php

namespace App\Controller;

use App\DataTables\LocalidadTableType;
use App\Entity\Localidad;
use App\Form\LocalidadType;
use App\Repository\LocalidadRepository;
use App\Repository\TurnoRepository;
use App\Repository\OficinaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Omines\DataTablesBundle\DataTableFactory;

/**
 * @Route("/localidad")
 */
class LocalidadController extends AbstractController
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
     * Route encargado de armar grilla de localidades
     * @Route("/", name="localidad_index")
     */
    public function index(Request $request): Response
    {
        $table = $this->datatableFactory->createFromType(LocalidadTableType::class, array())->handleRequest($request);
        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('localidad/index.html.twig', ['datatable' => $table]);
    }

    /**
     * @Route("/new", name="localidad_new", methods={"GET","POST"})
     * 
     * @IsGranted("ROLE_EDITOR")
     */
    public function new(Request $request): Response
    {
        $localidad = new Localidad();
        $form = $this->createForm(LocalidadType::class, $localidad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($localidad);
            $entityManager->flush();
            $this->addFlash('info', 'Se ha creado la localidad: ' . $localidad->getLocalidad());
            return $this->redirectToRoute('localidad_index');
        }

        return $this->render('localidad/new.html.twig', [
            'localidad' => $localidad,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="localidad_show", methods={"GET"})
     */
    public function show(Localidad $localidad): Response
    {
        return $this->render('localidad/show.html.twig', [
            'localidad' => $localidad,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="localidad_edit", methods={"GET","POST"})
     * 
     * @IsGranted("ROLE_EDITOR")
     */
    public function edit(Request $request, Localidad $localidad): Response
    {
        $form = $this->createForm(LocalidadType::class, $localidad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('localidad_index');
        }

        return $this->render('localidad/edit.html.twig', [
            'localidad' => $localidad,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="localidad_delete", methods={"DELETE"})
     * 
     * @IsGranted("ROLE_EDITOR")
     */
    public function delete(Request $request, Localidad $localidad): Response
    {
        if ($this->isCsrfTokenValid('delete' . $localidad->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($localidad);
            $entityManager->flush();
        }

        return $this->redirectToRoute('localidad_index');
    }

    /**
     * @Route("/{id}/borraDiaAgendaTurnosbyLocalidad", name="borraDiaAgendaTurnosbyLocalidad", methods={"GET", "POST"})
     * 
     * @IsGranted("ROLE_EDITOR")
     */
    public function borraDiaAgendaTurnosbyLocalidad(Request $request, TurnoRepository $turnoRepository, OficinaRepository $oficinaRepository, Localidad $localidad, LoggerInterface $logger): Response
    {
        //Construyo el formulario al vuelo
        $data = array(
            'fecha' => (new \DateTime(date("Y-m-d"))), // Campos del formulario
        );

        $form = $this->createFormBuilder($data)
            ->add('fechaDesde', DateType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'label' => 'Seleccione Fecha Desde la cual Borrar',
                'attr' => ['class' => 'text-danger js-datepicker'],
                'required' => true,
            ])
            ->add('fechaHasta', DateType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'label' => 'Seleccione Fecha Hasta la cual Borrar',
                'attr' => ['class' => 'text-danger js-datepicker'],
                'required' => true,
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fechaDesde = $request->request->get('form')['fechaDesde'];
            $fechaHasta = $request->request->get('form')['fechaHasta'];

            // Obtengo las Oficinas que pertenecen a la Localidad
            $oficinas = $oficinaRepository->findOficinaByLocalidad($localidad);

            // Establezco valores de fecha/hora desde/hasta para el día seleccionado
            $desde = (new \DateTime)->createFromFormat('d/m/Y H:i:s', $fechaDesde . '00:00:00');
            $hasta = (new \DateTime)->createFromFormat('d/m/Y H:i:s', $fechaHasta . '23:59:59');

            // Recorro cada oficina que pertenece a la localidad
            foreach ($oficinas as $oficina) {
                // Borro todos los turnos que no se encuentren asignados. Los cuento para informarlos después.
                $cantTurnosBorrados =  $turnoRepository->deleteTurnosByDiaByOficina($oficina['id'], $desde, $hasta);
                if ($cantTurnosBorrados) {
                    $this->addFlash('info', $oficina['oficina'] . ': ' . $cantTurnosBorrados . ' turnos borrados');
                    $logger->info(
                        'Turnos Borrados por Localidad',
                        [
                            'Oficina' => $oficina['oficina'],
                            'Localidad' => $localidad->getLocalidad(),
                            'Desde' => $desde->format('d/m/Y'),
                            'Hasta' => $hasta->format('d/m/Y'),
                            'Cantidad de Turnos' => $cantTurnosBorrados,
                            'Usuario' => $this->getUser()->getUsuario()
                        ]
                    );
                }
            }

            return $this->redirectToRoute('localidad_index');
        }

        return $this->render('localidad/borraDiaAgendaTurnos.html.twig', [
            'localidad' => $localidad,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/habilitaDeshabilitaOficinasByLocalidad", name="habilitaDeshabilitaOficinasByLocalidad", methods={"GET", "POST"})
     * 
     * @IsGranted("ROLE_EDITOR")
     */
    public function habilitaDeshabilitaOficinasByLocalidad(Request $request, TurnoRepository $turnoRepository, OficinaRepository $oficinaRepository, Localidad $localidad, LoggerInterface $logger): Response
    {
       
        if ($request->query->get('accion')) {

            $accion = $request->query->get('accion') === 'true' ? true: false;

            // Obtengo las Oficinas que pertenecen a la Localidad
            $oficinas = $oficinaRepository->findBy(['localidad' => $localidad]);
            
            // Recorro todas las oficinas de la localidad. Sólo actualizo si el estado es diferente.
            $conta = 0;
            foreach ($oficinas as $oficina) {
                if ($oficina->getHabilitada() <> $accion) {
                    $conta++;                   
                    $oficina->setHabilitada($accion);
                    $this->getDoctrine()->getManager()->flush();
                }
            }

            $logger->info(
                'Habilitación/Desehabilitación de Oficinas por Localidad',
                [
                    'Localidad' => $localidad->getLocalidad(),
                    'Acción' => $accion ? 'Habilitación' : 'Deshabilitación',
                    'Cantidad Total de Oficinas Procesadas' => count($oficinas),
                    'Cantidad de Oficinas' . $accion ? 'Habilitadas' : 'Deshabilitadas' => $conta,
                    'Usuario' => $this->getUser()->getUsuario()
                ]
            );        
    
            $this->addFlash('info', 'Se han ' . ($accion ? 'habilitado' : 'deshabilitado') . ' todas las oficinas de ' . $localidad->getLocalidad() . '. ' . $conta . '/' . count($oficinas) . ' fueron afectadas.');
                
        }

        return $this->redirectToRoute('localidad_index');
        
    }
}
