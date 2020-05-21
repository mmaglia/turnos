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
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use App\Entity\Turno;
use App\Repository\TurnoRepository;

use Symfony\Component\Form\Extension\Core\Type\DateType;
use DateTime;
use DateInterval;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

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
    public function addTurnos(Request $request, Oficina $oficina, TurnoRepository $turnoRepository, LoggerInterface $logger, OficinaRepository $oficinaRepository, SessionInterface $session, int $id=1): Response
    { 
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

        // Evalúa si se encuentran seleccionadas varias oficinas
        $oficinasSeleccionadas = '';
        $indiceOficinasSeleccionadas = 0;
        if ($session->get('oficinasSeleccionadas')) {
            $oficinasSeleccionadas = $session->get('oficinasSeleccionadas');
        }

        // Propone como fecha de generación el día siguiente al último turno
        $ultimoTurno = $turnoRepository->findUltimoTurnoByOficina($oficina);
        // Controla que existan turnos previos.
        if ($ultimoTurno) {
            $fechaHoraUltimoTurno = $ultimoTurno[0]->getFechaHora();
        } else { // Sino establece el día actual como punto de partida
            $fechaHoraUltimoTurno = new DateTime('now');
        }
        $aPartirde = new DateTime($fechaHoraUltimoTurno->format('Y-m-d'));
        $aPartirde = $aPartirde->add(new DateInterval('P1D')); // Suma un día al día actual

        // Establece valores por defecto y arma el Formulario
        $frecuencia = $oficina->getFrecuenciaAtencion();
        $cantidadDias = 90;
        $minutosDesplazamiento = 0;
        $form = $this->createForm(AddTurnosType::class, [
            'fechaInicio' => $aPartirde,
            'minutosDesplazamiento' => $minutosDesplazamiento,
            'cantTurnosSuperpuestos' => 1,
            'cantidadDias' => $cantidadDias,
            'soloUnTurno' => false,
            ]);
        $form->handleRequest($request);

        // Procesa Datos del Formulario
        if ($form->isSubmitted() && $form->isValid()) {

            $feriados = $request->request->get('add_turnos')['feriados'];
            $aFeriados = explode(',', $feriados);
            
            $minutosDesplazamiento = $request->request->get('add_turnos')['minutosDesplazamiento'];

            // Si se opta por "Sólo un turno por rango horario" cantTurnosSuperpuestos puede no llegar como parámetro. Para ese caso se establece por defecto el valor 1.
            $cantTurnos = (isset($request->request->get('add_turnos')['cantTurnosSuperpuestos']) ? $request->request->get('add_turnos')['cantTurnosSuperpuestos'] : 1);

            $entityManager = $this->getDoctrine()->getManager();
            $idTurnosGenerados = [];

            while (true) {                
                if ($oficinasSeleccionadas) {
                    // Establece los valores de fecha de Inicio y Frecuencia por cada Oficina que se seleccionó
                    $oficina = $oficinaRepository->findById($oficinasSeleccionadas[$indiceOficinasSeleccionadas]);
                    $frecuencia = $oficina->getFrecuenciaAtencion();
                    $fechaHoraUltimoTurno = $oficinaRepository->findUltimoTurnoById($oficinasSeleccionadas[$indiceOficinasSeleccionadas]);
                    // Controla que existan turnos previos
                    if ($fechaHoraUltimoTurno) {
                        $aPartirde = new DateTime($fechaHoraUltimoTurno);
                    } else { // Sino establece el día actual como punto de partida
                        $aPartirde = new DateTime('now');
                    }
                    $fechaInicio = $aPartirde->format('d/m/Y');

                    $soloUnTurno = false; // Evito un control innecesario de buscar si existe el turno. Todos son turnos nuevos.
                }
                else {
                    $fechaInicio = $request->request->get('add_turnos')['fechaInicio'];
                    $soloUnTurno = isset($request->request->get('add_turnos')['soloUnTurno']);

                    // Establece las 0hs del día seleccionado en formato DateTime
                    $aPartirde = new \DateTime(substr($fechaInicio,-4) . '-' . substr($fechaInicio,3,2) . '-' . substr($fechaInicio, 0,2));
                    $aPartirde->sub(new DateInterval('P1D')); // Resta un día al día de comienzo porque incremento al comienzo del bucle siguiente
                }

                // Obtiene cantidad de días para la generación
                if ($request->request->get('add_turnos')['cantidadDias']) {
                    $cantidadDias = $request->request->get('add_turnos')['cantidadDias'];
                } else {
                    // Si se optó por una fecha 'Hasta' se calcula la diferencia en días entre el inicio y el fin
                    $fechaFin = $request->request->get('add_turnos')['fechaFin'];
                    $fechaFin = new \DateTime(substr($fechaFin,-4) . '-' . substr($fechaFin,3,2) . '-' . substr($fechaFin, 0,2));
                    $cantidadDias = $fechaFin->diff(($aPartirde))->days + 1;
                }

                // Me aseguro que sólo se genere un turno por rango si no se quiere más que eso (sobre todo por si viene por generaicón múltiple)
                if ($soloUnTurno) {
                    $cantTurnos = 1; 
                }

                // Recorre cada día del intervalo indicado
                $totalTurnosGenerados = 0;
                $nuevoTurno = $aPartirde;
                for ($dia = 1; $dia <= $cantidadDias; $dia++){    
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
                                $totalTurnosGenerados++;
                                $turno = new Turno();
                                $turno->setFechaHora($nuevoTurno);
                                $turno->setOficina($oficina);
                                $turno->setEstado(1);
                                $entityManager->persist($turno);       
                               
                                $idTurnosGenerados[] = $turno->getId(); // Guarda información para Deshacer
                                $this->getDoctrine()->getManager()->flush();
                            }
                        }
                        
                        $nuevoTurno = $aPartirde->add(new DateInterval('PT' . $frecuencia . 'M'));

                        if ($nuevoTurno >= $ultimoTurnoDelDia) {
                            break;
                        }
                    }
                }

                $this->addFlash('info', $oficina . ': ' . $totalTurnosGenerados . ' turnos nuevos. Ultimo turno Generado: ' . $nuevoTurno->format('d/m/Y'));

                $logger->info('Creación de Nuevos Turnos', [
                    'Oficina' => $oficina->getOficinayLocalidad(), 
                    'Desde' => $fechaInicio,
                    'Hasta' => $nuevoTurno->format('d/m/Y'), 
                    'Feriados' => $feriados,
                    'Cant. Turnos Superpuestos' => $cantTurnos,
                    'Minutos Desplazamiento' => $minutosDesplazamiento,
                    'Cant. de Días' => $cantidadDias,
                    'Sólo un Turno' => $soloUnTurno,
                    'Turnos Generados' => $totalTurnosGenerados,
                    'Usuario' => $this->getUser()->getUsuario()
                    ]
                );

                // Condición de Salida del While (no se seleccionaron Oficinas o se recorrieron todas las que se habían seleccionado)
                if (!$oficinasSeleccionadas || $indiceOficinasSeleccionadas++ == count($oficinasSeleccionadas)-1) {
                    // En caso de existir, libero la selección de session
                    $session->set('oficinasSeleccionadas', null);

                    break;
                }
            }

            //Guarda ID de Turnos Generados en Session
            $session->set('idTurnosGenerados', $idTurnosGenerados);
            
            // TODO ver de notificar la cantidad de turnos creados
            return $this->redirectToRoute('oficina_index');
        }

        return $this->render('oficina/add_turnos.html.twig', [
            'oficina' => $oficina,
            'fechaHoraUltimoTurno' => $fechaHoraUltimoTurno,
            'aPartirde' => $aPartirde,
            'oficinasSeleccionadas' => $oficinasSeleccionadas,
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
     * @Route("/deshaceUltimaGeneracion", name="oficina_deshacer", methods={"GET","POST"})
     */
    public function deshaceUltimaGeneracion(SessionInterface $session, TurnoRepository $turnoRepository)
    {
        $idTurnosGenerados = $session->get('idTurnosGenerados');
        $cont=0;

        if ($idTurnosGenerados) {
            foreach($idTurnosGenerados as $idTurno) {
                $cont++;
                $turno = $turnoRepository->findById($idTurno);
                $this->getDoctrine()->getManager()->remove($turno);
                $this->getDoctrine()->getManager()->flush();
            }
        }
        if ($cont) {
            $this->addFlash('info', $cont . ' Turnos revertidos. Los mismos han sido eliminados.');
        } else {
            $this->addFlash('info', ' Nada para deshacer.');
        }

        // Limpio la info almacenada en session para deshacer
        $session->set('idTurnosGenerados', null);

        return $this->redirectToRoute('oficina_index');
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


    /**
     * @Route("/addTurnosOficinas", name="oficina_addTurnosOficinas", methods={"GET","POST"})
     */
    public function addTurnosOficinas(Request $request, SessionInterface $session)
    {

        // Guardo selección de oficinas en session y llamo al método para agregar turnos
        $session->set('oficinasSeleccionadas', $request->request->get('oficinaID'));

        return $this->redirectToRoute('oficina_addTurnos');

    }


    

}
