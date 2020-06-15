<?php

namespace App\Controller;

use App\Entity\Localidad;
use App\Form\LocalidadType;
use App\Repository\LocalidadRepository;
use App\Repository\TurnoRepository;
use App\Repository\OficinaRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;

/**
 * @Route("/localidad")
 */
class LocalidadController extends AbstractController
{
    use DataTablesTrait;

    protected $datatableFactory;

    public function __construct(DataTableFactory $datatableFactory) {
          $this->datatableFactory = $datatableFactory;
    }
    
    /**
     * @Route("/", name="localidad_index")
     */
    public function index(Request $request, LocalidadRepository $localidadRepository, PaginatorInterface $paginator): Response
    {


        $table = $this->datatableFactory->create([])
            ->add('id', TextColumn::class, ['label' => 'ID'])
            ->add('localidad', TextColumn::class, 
                [   'label' => 'Localidad',
                    'render' => function($value, $context) {
                        return sprintf('<a href="' . 
                            $this->generateUrl('localidad_show', ['id' =>
                            $context->getId()]) . '">%s</a>', $value, $value);}])
            ->createAdapter(ORMAdapter::class, [
                    'entity' => Localidad::class,
                ])
            ->handleRequest($request);

            if ($table->isCallback()) {
                return $table->getResponse();
            }
    
            return $this->render('localidad/index.html.twig',['datatable' => $table]);
/*
        $localidades = $paginator->paginate($localidadRepository->findAllOrdenado(), $request->query->getInt('page', 1), 50);
        return $this->render('localidad/index.html.twig', [
            'localidades' => $localidades,
        ]);
*/        
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
            ->add('fecha', DateType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'label' => 'Seleccione Fecha a Borrar',
                'attr' => ['class' => 'text-danger js-datepicker'],
                'required' => true,
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fechaSeleccionada = $request->request->get('form')['fecha'];

            // Obtengo las Oficinas que pertenecen a la Localidad
            $oficinas = $oficinaRepository->findOficinaByLocalidad($localidad);

            // Establezco valores de fecha/hora desde/hasta para el día seleccionado
            $desde = (new \DateTime)->createFromFormat('d/m/Y H:i:s', $fechaSeleccionada . '00:00:00');
            $hasta = (new \DateTime)->createFromFormat('d/m/Y H:i:s', $fechaSeleccionada . '23:59:59');

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
}
