<?php

namespace App\Controller;

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

/**
 * @Route("/localidad")
 */
class LocalidadController extends AbstractController
{
    /**
     * @Route("/", name="localidad_index", methods={"GET"})
     */
    public function index(LocalidadRepository $localidadRepository): Response
    {
        return $this->render('localidad/index.html.twig', [
            'localidads' => $localidadRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="localidad_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

        $localidad = new Localidad();
        $form = $this->createForm(LocalidadType::class, $localidad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($localidad);
            $entityManager->flush();

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
     */
    public function edit(Request $request, Localidad $localidad): Response
    {
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

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
     */
    public function delete(Request $request, Localidad $localidad): Response
    {
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

        if ($this->isCsrfTokenValid('delete'.$localidad->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($localidad);
            $entityManager->flush();
        }

        return $this->redirectToRoute('localidad_index');
    }

    /**
     * @Route("/{id}/borraDiaAgendaTurnosbyLocalidad", name="borraDiaAgendaTurnosbyLocalidad", methods={"GET", "POST"})
     */
    public function borraDiaAgendaTurnosbyLocalidad(Request $request, TurnoRepository $turnoRepository, OficinaRepository $oficinaRepository, Localidad $localidad, LoggerInterface $logger): Response
    {
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

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
                'required' =>true,
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
                    $logger->info('Turnos Borrados por Localidad', [
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
