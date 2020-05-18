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

use Symfony\Component\Form\Extension\Core\Type\DateType;
use DateTime;
use DateInterval;
use Psr\Log\LoggerInterface;

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
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

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
    public function addTurnos(Request $request, Oficina $oficina, TurnoRepository $turnoRepository, LoggerInterface $logger): Response
    {
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

        $ultimoTurno = $turnoRepository->findUltimoTurnoByOficina($oficina);

        // Controla que existan turnos previos.
        if ($ultimoTurno) {
            $fechaHoraUltimoTurno = $ultimoTurno[0]->getFechaHora();
        } else { // Sino establece el día actual como punto de partida
            $fechaHoraUltimoTurno = new DateTime('now');
        }

        $aPartirde = new DateTime($fechaHoraUltimoTurno->format('Y-m-d'));
        $aPartirde = $aPartirde->add(new DateInterval('P1D')); // Suma un día al día actual

        // Establece valores por defecto
        $frecuencia = $oficina->getFrecuenciaAtencion();
        $cantidadDias = 90;
        $minutosDesplazamiento = 0;

        $form = $this->createForm(AddTurnosType::class, [
            'fechaInicio' => $aPartirde,
            'minutosDesplazamiento' => $minutosDesplazamiento,
            'cantTurnosSuperpuestos' => 1,
            'cantidadDias' => $cantidadDias,
            'soloUnTurno' => false,
            ] );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fechaInicio = $request->request->get('add_turnos')['fechaInicio'];
            $feriados = $request->request->get('add_turnos')['feriados'];
            $minutosDesplazamiento = $request->request->get('add_turnos')['minutosDesplazamiento'];
            // Si se opta por "Sólo un turno por rango horario" cantTurnosSuperpuestos puede no llegar como parámetro. Para ese caso se establece por defecto el valor 1.
            $cantTurnos = (isset($request->request->get('add_turnos')['cantTurnosSuperpuestos']) ? $request->request->get('add_turnos')['cantTurnosSuperpuestos'] : 1);
            $cantidadDias = $request->request->get('add_turnos')['cantidadDias'];
            $soloUnTurno = isset($request->request->get('add_turnos')['soloUnTurno']);

            if ($soloUnTurno) {
                $cantTurnos = 1; // Me aseguro que sólo se genere un turno por rango si no se quiere más que eso
            }

            $aFeriados = explode(',', $feriados);

            // Establece las 0hs del día seleccionado en formato DateTime
            $aPartirde = new \DateTime(substr($fechaInicio,-4) . '-' . substr($fechaInicio,3,2) . '-' . substr($fechaInicio, 0,2));
            $aPartirde->sub(new DateInterval('P1D')); // Resta un día al día de comienzo porque incremento al comienzo del bucle siguiente
            $entityManager = $this->getDoctrine()->getManager();

            // Recorre cada día del intervalo indicado
            $j=0;
            for ($i=1; $i <= $cantidadDias; $i++){
                    
                // Incrementa fecha en un 1 día
                $nuevoTurno = $aPartirde->add(new DateInterval('P1D'));

                // Verifico que no sea sábado (6) o domingo (7)
                if ($nuevoTurno->format('N') >= 6) {
                    continue; // Salteo el día
                }

                // Verifico que no esté en la lista de feriados
                if (in_array($nuevoTurno->format('d/m/Y'), $aFeriados)) {
                    continue; // Salteo el día
                }

                // Establece la hora máxima para el día que se está generando
                $ultimoTurnoDelDia = new DateTime($nuevoTurno->format('Y-m-d H:i'));
                $ultimoTurnoDelDia = $ultimoTurnoDelDia->setTime($oficina->getHoraFinAtencion()->format('H'), $oficina->getHoraFinAtencion()->format('i'));
                $ultimoTurnoDelDia->add(new DateInterval('PT' . $minutosDesplazamiento . 'M'));

                // Establece la hora de Inicio de Atención
                $nuevoTurno = $nuevoTurno->setTime($oficina->getHoraInicioAtencion()->format('H'), $oficina->getHoraInicioAtencion()->format('i'));
                $nuevoTurno->add(new DateInterval('PT' . $minutosDesplazamiento . 'M'));
                
                // Recorre intervalos para el día en proceso
                while (true) {
                    $existeTurno = false;
                    if ($soloUnTurno) {
                        // Verifica si el turno existe
                        $existeTurno = count($turnoRepository->findTurno($oficina, $nuevoTurno));
                    }

                    if (!$existeTurno || !$soloUnTurno) {
                        // Genera el alta del turno (simple por inexistencia previa o múltiples para el mismo horario)
                        for ($k=1; $k <= $cantTurnos; $k++) {
                            $j++;
                            $turno = new Turno();
                            $turno->setFechaHora($nuevoTurno);
                            $turno->setOficina($oficina);
                            $turno->setEstado(1);
                            $entityManager->persist($turno);       
                            $this->getDoctrine()->getManager()->flush();
                        }
                    }

                    $nuevoTurno = $aPartirde->add(new DateInterval('PT' . $frecuencia . 'M'));

                    if ($nuevoTurno > $ultimoTurnoDelDia) {
                        break;
                    }

                }
            }

            $this->addFlash('info', $oficina . ': ' . $j . ' turnos nuevos. Ultimo turno Generado: ' . $nuevoTurno->format('d/m/Y'));

            $logger->info('Creación de Nuevos Turnos', [
                'Oficina' => $oficina->getOficinayLocalidad(), 
                'Desde' => $fechaInicio,
                'Hasta' => $nuevoTurno->format('d/m/Y'),
                'Feriados' => $feriados,
                'Cant. Turnos Superpuestos' => $cantTurnos,
                'Minutos Desplazamiento' => $minutosDesplazamiento,
                'Cant. de Días' => $cantidadDias,
                'Sólo un Turno' => $soloUnTurno,
                'Turnos Generados' => $j,
                'Usuario' => $this->getUser()->getUsuario()
                ]
            );

            // TODO ver de notificar la cantidad de turnos creados
            return $this->redirectToRoute('oficina_index');
        }

        return $this->render('oficina/add_turnos.html.twig', [
            'oficina' => $oficina,
            'fechaHoraUltimoTurno' => $fechaHoraUltimoTurno,
            'aPartirde' => $aPartirde,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/{id}/borraDiaAgendaTurnosbyOficina", name="borraDiaAgendaTurnosbyOficina", methods={"GET", "POST"})
     */
    public function borraDiaAgendaTurnosbyOficina(Request $request, Oficina $oficina, TurnoRepository $turnoRepository, LoggerInterface $logger): Response
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

            // Establezco valores de fecha/hora desde/hasta para el día seleccionado
            $desde = (new \DateTime)->createFromFormat('d/m/Y H:i:s', $fechaSeleccionada . '00:00:00');
            $hasta = (new \DateTime)->createFromFormat('d/m/Y H:i:s', $fechaSeleccionada . '23:59:59');

            // Borro todos los turnos que no se encuentren asignados. Los cuento para informarlos después.
            $cantTurnosBorrados =  $turnoRepository->deleteTurnosByDiaByOficina($oficina->getId(), $desde, $hasta);
            if ($cantTurnosBorrados) {
                $this->addFlash('info', $oficina . ': ' . $cantTurnosBorrados . ' turnos borrados');
                $logger->info('Turnos Borrados por Oficina', [
                    'Oficina' => $oficina->getOficinayLocalidad(), 
                    'Desde' => $desde->format('d/m/Y'),
                    'Hasta' => $hasta->format('d/m/Y'),
                    'Cantidad de Turnos' => $cantTurnosBorrados,
                    'Usuario' => $this->getUser()->getUsuario()
                    ]
                );
            }

            return $this->redirectToRoute('oficina_index');
        }

        return $this->render('oficina/borraDiaAgendaTurnosOficina.html.twig', [
            'oficina' => $oficina,
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
