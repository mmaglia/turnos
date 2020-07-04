<?php

namespace App\Controller;

use App\Entity\Oficina;
use App\Form\OficinaType;
use App\Form\AddTurnosType;
use App\Repository\OficinaRepository;
use Knp\Component\Pager\PaginatorInterface;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/oficina")
 */
class OficinaController extends AbstractController
{   

    /**
     * @Route("/", name="oficina_index", methods={"GET"})
     */
    public function index(Request $request, OficinaRepository $oficinaRepository, PaginatorInterface $paginator): Response
    {

        $oficinas = $oficinaRepository->findAllWithUltimoTurno(); // Lista completa de Oficinas

        // Si el usuario conectado está asociado a una oficina que admite AutoGestión, filtra la lista de oficinas para que contenga sólo la Oficina del usuario
        if ($this->getUser()->getOficina()->getAutoGestion()) {
            $oficinaId = $this->getUser()->getOficina()->getId();   // Obtengo Id de la Oficina asociada al Usuario
            $key = array_search($oficinaId, array_column($oficinas, 'id')); // Busco en la colección de $oficinas la oficina del usuario
            $oficinas = [$oficinas[$key]]; // Filtro la lista de $oficinas únicamente para que contenga la Oficina del Usuario
        }
         
        $listaOficinas =  $paginator->paginate($oficinas, $request->query->getInt('page', 1), 50);

        return $this->render('oficina/index.html.twig', [
            'oficinas' =>  $listaOficinas
        ]);
    }

    /**
     * @Route("/new", name="oficina_new", methods={"GET","POST"})
     * 
     * @IsGranted("ROLE_EDITOR")
     */
    public function new(Request $request): Response
    {
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
     * 
     * @IsGranted("ROLE_EDITOR")
     */
    public function addTurnos(Request $request, Oficina $oficina, TurnoRepository $turnoRepository, LoggerInterface $logger, OficinaRepository $oficinaRepository, SessionInterface $session, int $id=1): Response
    {        
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

            $idTurnosGenerados = [];

            while (true) {                
                if ($oficinasSeleccionadas) {
                    // Establece los valores de fecha de Inicio y Frecuencia por cada Oficina que se seleccionó
                    $oficina = $oficinaRepository->findById($oficinasSeleccionadas[$indiceOficinasSeleccionadas]);
                    $fechaHoraUltimoTurno = $oficinaRepository->findUltimoTurnoById($oficinasSeleccionadas[$indiceOficinasSeleccionadas]);
                    $frecuencia = $oficina->getFrecuenciaAtencion();               

                    // Controla que existan turnos previos
                    if ($fechaHoraUltimoTurno) {
                        $aPartirde = new DateTime($fechaHoraUltimoTurno);
                    } else { // Sino establece el día actual como punto de partida
                        $aPartirde = new DateTime('now');
                    }
                    $fechaInicio = $aPartirde->format('d/m/Y'); // Para auditar en el log con el formato adecuado

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
                    $logCantidadDias = $cantidadDias;
                } else {
                    // Si se optó por una fecha 'Hasta' se calcula la diferencia en días entre el inicio y el fin
                    $logFechaFin = $request->request->get('add_turnos')['fechaFin'];
                    $fechaFin = $request->request->get('add_turnos')['fechaFin'];
                    $fechaFin = new \DateTime(substr($fechaFin,-4) . '-' . substr($fechaFin,3,2) . '-' . substr($fechaFin, 0,2));
                    $cantidadDias = $fechaFin->diff(($aPartirde))->days + 1;
                    $logCantidadDias = $cantidadDias-1; 
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
//                    $ultimoTurnoDelDia->add(new DateInterval('PT' . $minutosDesplazamiento . 'M'));

                    // Establece la hora de Inicio de Atención
                    $nuevoTurno = $nuevoTurno->setTime($oficina->getHoraInicioAtencion()->format('H'), $oficina->getHoraInicioAtencion()->format('i'));
                    $nuevoTurno->add(new DateInterval('PT' . $minutosDesplazamiento . 'M'));
                    
                    // Recorre intervalos para el día en proceso
                    while (true) {
                        $existeTurno = false;
                        if ($soloUnTurno) {
                            $existeTurno = count($turnoRepository->findTurno($oficina, $nuevoTurno)); // Verifica si el turno existe
                        }

                        if (!$existeTurno || !$soloUnTurno) {
                            // Genera el alta del turno (simple por inexistencia previa o múltiples para el mismo horario)                           
                            for ($k=1; $k <= $cantTurnos; $k++) {
                                $totalTurnosGenerados++;
                                $turno = new Turno();
                                $turno->setFechaHora($nuevoTurno);
                                $turno->setOficina($oficina);
                                $turno->setEstado(1);

                                $this->getDoctrine()->getManager()->persist($turno);    
                                $this->getDoctrine()->getManager()->flush();

                                $idTurnosGenerados[] = $turno->getId(); // Guarda información para Deshacer

                            }
                        }
                        
                        $nuevoTurno = $aPartirde->add(new DateInterval('PT' . $frecuencia . 'M'));
                        
                        if ($nuevoTurno >= $ultimoTurnoDelDia) {
                            break;
                        }
                    }
                }

                $this->addFlash('info', $oficina . ': ' . $totalTurnosGenerados . ' turnos nuevos. Ultimo turno Generado: ' . ($request->request->get('add_turnos')['cantidadDias'] ? $nuevoTurno->format('d/m/Y') : $logFechaFin));

                $logger->info('Creación de Nuevos Turnos', [
                    'Oficina' => $oficina->getOficinayLocalidad(), 
                    'Desde' => $fechaInicio,
                    'Hasta' => ($request->request->get('add_turnos')['cantidadDias'] ? $nuevoTurno->format('d/m/Y') : $logFechaFin), 
                    'Feriados' => $feriados,
                    'Cant. Turnos Superpuestos' => $cantTurnos,
                    'Minutos Desplazamiento' => $minutosDesplazamiento,
                    'Cant. de Días' => $logCantidadDias,
                    'Sólo un Turno' => $soloUnTurno,
                    'Turnos Generados' => $totalTurnosGenerados,
                    'Usuario' => $this->getUser()->getUsuario()
                    ]
                );

                // Condición de Salida del While (no se seleccionaron Oficinas o se recorrieron todas las que se habían seleccionado)
                if (!$oficinasSeleccionadas || $indiceOficinasSeleccionadas++ == count($oficinasSeleccionadas)-1) {
                    break;
                }
            }

            //Guarda ID de Turnos Generados en Session por si se desea deshacer
            $session->set('idTurnosGenerados', $idTurnosGenerados);
            $session->remove('oficinasSeleccionadas'); // En caso de existir, libero la selección de session
            
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
     * 
     * @IsGranted("ROLE_EDITOR")
     */
    public function borraDiaAgendaTurnosbyOficina(Request $request, Oficina $oficina, TurnoRepository $turnoRepository, LoggerInterface $logger): Response
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
    public function deshaceUltimaGeneracion(SessionInterface $session, TurnoRepository $turnoRepository, LoggerInterface $logger)
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

            $logger->info('Deshacer Creación de Nuevos Turnos', [
                'Cant. de Turnos Revertidos' => $cont,
                'Usuario' => $this->getUser()->getUsuario()
                ]
            );

        } else {
            $this->addFlash('info', ' Nada para deshacer.');
        }


        // Limpio la info almacenada en session para deshacer
        $session->remove(('idTurnosGenerados'));
        $session->remove('oficinasSeleccionadas');

        return $this->redirectToRoute('oficina_index');
    }


    /**
     * @Route("/autoExtend/{oficinaIdDesde}/{oficinaIdHasta}/{cantidadDias}", name="oficina_addTurnos_autoExtend", methods={"GET","POST"})
     * 
     * @IsGranted("IS_AUTHENTICATED_ANONYMOUSLY")
     */
    public function addTurnos_autoExtend(Request $request, TurnoRepository $turnoRepository, LoggerInterface $logger, OficinaRepository $oficinaRepository, SessionInterface $session, int $oficinaIdDesde, int $oficinaIdHasta, $cantidadDias=1): Response
    {        

        $inicioProceso = (new \DateTime());

        // Recibo parámetros
        $oficinaIdDesde = $request->attributes->get('_route_params')['oficinaIdDesde'];
        $oficinaIdHasta = $request->attributes->get('_route_params')['oficinaIdHasta'];
        $cantidadDias       = $request->attributes->get('_route_params')['cantidadDias'];

        // Obtengo lista de Oficinas en el rango de ID indicados que tienen activa la funcionalidad de autoExtend
        $oficinas = $oficinaRepository->findOficinasAutoExtend($oficinaIdDesde, $oficinaIdHasta);

        // Valido parámetros
        if ($oficinaIdDesde > 0 && $oficinaIdHasta > 0 && $oficinaIdHasta > $oficinaIdDesde && $oficinas) {
            $cantOficinas = 0;
            $totalTurnosGenerados = 0;

            foreach ($oficinas as $oficina) {
                // Se establece cuales son los días feriados (definidos en el .env a nivel de aplicación)
                $feriados = '';
                if ($oficina['circunscripcion'] == 1 || $oficina['circunscripcion'] == 4 || $oficina['circunscripcion'] == 5 ) {
                    $feriados = $_ENV['FERIADOS_SANTA_FE'];
                }
                if ($oficina['circunscripcion'] == 2 || $oficina['circunscripcion'] == 3 ) {
                    $feriados = $_ENV['FERIADOS_ROSARIO'];
                }
                $aFeriados = explode(',', $feriados);

                $ultimoTurno = $turnoRepository->findUltimoTurnoByOficina($oficina['id']);
                if ($ultimoTurno) {     // Verifica que la Oficina tenga generado al menos un turno
                                        // Sino, no procesa porque la generación se basa en la copia de turnos del último día
                    $cantOficinas++;
                    $ultimoTurno = $ultimoTurno[0]->getFechaHora();

                    // Obtiene todos los turnos del último día de la Oficina
                    $turnosUltimoDia = $turnoRepository->findTurnosByFecha($oficina['id'], $ultimoTurno);

                    $i = 0;
                    while (true) {  // Busca un día válido para generar turnos
                        // Incrementa fecha en un 1 día
                        $fechaTurno = $ultimoTurno->add(new DateInterval('P1D'));

                        // Verifico que no sea sábado (6) o domingo (7)
                        if ($fechaTurno->format('N') >= 6) {
                            continue; // Salteo el día
                        }

                        // Verifico que no sea el mes de enero
                        if ($fechaTurno->format('n') == 1) {
                            continue; // Salteo el día
                        }

                        // Verifico que no esté en la lista de feriados
                        if (in_array($fechaTurno->format('d/m/Y'), $aFeriados)) {
                            continue; // Salteo el día
                        }

                        // Encontrado el dia válido, se generan nuevos turnos para ese día a partir de los turnos del último día generado
                        // El turno se establece para la fecha encontrada y para la hora correspondiente al día anterior
                        // Se obtiene así un esquema idéntico de turnos, tanto en cantidad como en frecuencia a partir del último día de la Oficina
                        foreach ($turnosUltimoDia as $turno) {
                            $nuevoTurno = new Turno();
                            $nuevoTurno->setFechaHora(new DateTime($fechaTurno->format('Y-m-d ') . $turno->getFechaHora()->format('H:i:s')));
                            $nuevoTurno->setOficina($oficinaRepository->findById($oficina['id']));
                            $nuevoTurno->setEstado(1);

                            $totalTurnosGenerados++;

                            $this->getDoctrine()->getManager()->persist($nuevoTurno);    
                            $this->getDoctrine()->getManager()->flush();
                        }

                        // Itera en función a la cant. de días que se pasa argumento
                        if (++$i == $cantidadDias) {
                            break; 
                        }
                    }
                }
            }

            $finProceso = (new \DateTime());
            $logger->info('Creación Automática de Turnos', [
                'OficinaIdDesde' => $oficinaIdDesde,
                'OficinaIdHasta' => $oficinaIdHasta,
                'Cant. de Días'  => $cantidadDias,
                'Cant. de Oficinas que Generaron' => $cantOficinas,
                'Cant. de Turnos Totales' => $totalTurnosGenerados,
                'Iniciado' => $inicioProceso->format('Y-m-d H:i:s'),
                'Fin' => $finProceso->format('Y-m-d H:i:s'),
                'Tiempo' => $inicioProceso->diff($finProceso)->format('%i minutos %s segundos'),
                'IP' => $request->getClientIp()
                ]
            );

            return new JsonResponse("Proceso Finalizado");

        }

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
     * 
     * @IsGranted("ROLE_EDITOR")
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
    public function addTurnosOficinas(Request $request, OficinaRepository $oficinaRepository, SessionInterface $session)
    {

        $idSeleccionados = $request->request->get('oficinaID');

        // Guardo selección de oficinas en session y llamo al método para agregar turnos
        $session->set('oficinasSeleccionadas', $idSeleccionados);

        return $this->redirectToRoute('oficina_addTurnos');

    }
    
}
