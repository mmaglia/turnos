<?php

namespace App\Controller;

use App\Repository\TurnoRepository;
use App\Repository\OficinaRepository;
use App\Repository\TurnosDiariosRepository;
use App\Repository\CircunscripcionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\ColumnChart;
use Symfony\Component\HttpFoundation\JsonResponse;

class EstadisticaController extends AbstractController
{
    /**
     * @Route("/estadistica", name="estadistica_index", methods={"GET", "POST"})
     */
    public function index(OficinaRepository $oficinaRepository): Response
    {

        // Propone fechas (dese el día actual hasta un 1 mes más adelante)
        $desde = new \DateTime(date("Y-m-d") . " 00:00:00");
        $hasta = new \DateTime("+1 months");

        // Busca los turnos en función a los estados de la oficina a la que pertenece el usuario
        if ($this->isGranted('ROLE_USER')) {
            $oficinaUsuario = $this->getUser()->getOficina();
        }
        
        $oficinas = $oficinaRepository->findAllOficinas();

        return $this->render('estadistica/index.html.twig', [
            'desde' => $desde->format('d/m/Y'),
            'hasta' => $hasta->format('d/m/Y'),
            'oficinas' => $oficinas,
            'oficinaUsuario' => $oficinaUsuario
        ]);
    }


    /**
     * @Route("/estadistica/evolucionDiaria", name="estadistica_evolucion_diaria", methods={"GET", "POST"})
     */
    public function evolucionDiaria(OficinaRepository $oficinaRepository): Response
    {

        // Propone fechas (dese el día actual hasta un 1 mes más adelante)
        $desde = new \DateTime("-1 months");
        $hasta = new \DateTime(date("Y-m-d") . " 00:00:00");

        // Busca los turnos en función a los estados de la oficina a la que pertenece el usuario
        if ($this->isGranted('ROLE_USER')) {
            $oficinaUsuario = $this->getUser()->getOficina();
        }
        
        $oficinas = $oficinaRepository->findAllOficinas();

        return $this->render('estadistica/indexEvolucionDiaria.html.twig', [
            'desde' => $desde->format('d/m/Y'),
            'hasta' => $hasta->format('d/m/Y'),
            'oficinas' => $oficinas,
            'oficinaUsuario' => $oficinaUsuario
        ]);
    }
 
    
    /**
     * @Route("/estadistica/informeOcupacionAgenda", name="informe_ocupacion_agenda", methods={"GET", "POST"})
     */
    public function informeOcupacionAgenda(OficinaRepository $oficinaRepository): Response
    {

        // Propone fecha (día actual)
        $desde = new \DateTime(date("Y-m-d") . " 00:00:00");
       
        return $this->render('estadistica/indexInformeOcupacionDiaria.html.twig', [
            'desde' => $desde->format('d/m/Y'),
        ]);
    }


    /**
     * @Route("/estadistica/informeOcupacionPlena", name="informe_ocupacion_plena", methods={"GET", "POST"})
     */
    public function informeOcupacionPlena(OficinaRepository $oficinaRepository): Response
    {

        // Propone fecha (día actual)
        $desde = new \DateTime(date("Y-m-d") . " 00:00:00");
       
        return $this->render('estadistica/indexInformeOcupacionPlena.html.twig', [
            'desde' => $desde->format('d/m/Y'),
        ]);
    }
    
     

    /**
     * @Route("/estadistica/show", name="estadistica_show", methods={"GET", "POST"})
     */
    public function show(Request $request, OficinaRepository $oficinaRepository, TurnoRepository $turnoRepository, LoggerInterface $logger): Response
    {
        // Recibe variables del Formulario
        if ($request->request->get('start')) {
            $desde = $request->request->get('start');
            $hasta = $request->request->get('end');
            $oficinaId = $request->request->get('oficinas');
            $vistaGeneral = $request->request->get('general');
            $vistaSemanal = $request->request->get('semanal');
            $vistaDetallada = $request->request->get('detallado');
            $vistaSinTurno = $request->request->get('diasSinTurnos');
            $vistaSoloSinTurno = $request->request->get('soloDiasSinTurnos');
        }

        if (isset($_GET['start'])) {
            $desde = $_GET['start'];
            $hasta = $_GET['end'];
            $oficinaId = $_GET['oficinas'];
            $vistaGeneral = $_GET['general'];
            $vistaSemanal = $_GET['semanal'];
            $vistaDetallada = $_GET['detallado'];
            $vistaSinTurno = $_GET['diasSinTurnos'];
            $vistaSoloSinTurno = $_GET['soloDiasSinTurnos'];
        }

        // Agrega rango horario a las fechas seleccionadas
        $desde = $desde . ' 00:00:00';
        $hasta = $hasta . ' 23:59:59';

        if (!isset($oficinaId)) {
            // Busca la oficina a la que pertenece el Usuario
            if ($this->getUser()->getOficina()) {
                $oficinaId = $this->getUser()->getOficina()->getId();
            }
            if (!$oficinaId) {
                // Por seguridad, si el usuario no tiene vinculada oficina pre establece "TODAS"
                $oficinaId = 0;
            }
        }

        //Busco Oficina si es necesario para mostrar
        if ($oficinaId) {
            $oficina = $oficinaRepository->findById($oficinaId);
        }
        else {
            $oficina = 'de Todas las Oficinas';
        }

        //Obtengo Estadística General
        if ($vistaGeneral) {
            $estadisticaGeneral = $turnoRepository->findEstadistica($desde, $hasta, $oficinaId);
        } else {
            $estadisticaGeneral = [];
        }

        //Obtengo Estadística Semanal
        if ($vistaSemanal) {
            //Transforma fecha a objetos DateTime
            $DTdesde = new \DateTime(substr($desde, 6, 4) . '-' . substr($desde,3,2) . '-' . substr($desde, 0,2) . " 00:00:00");
            $DThasta = new \DateTime(substr($hasta,6, 4) . '-' . substr($hasta,3,2) . '-' . substr($hasta, 0,2) . " 23:59:59");

            $semanaPrimerDia = $DTdesde;
            $i = 0;
            $estadisticaSemanal = [];
            while (true) {
                if ($i == 0) {
                    $semanaDesde = $semanaPrimerDia->format('d/m/Y H:i:s');
                } else {
                    $semanaDesde = $semanaPrimerDia->modify('+1 days')->format('d/m/Y H:i:s');
                }

                $semanaHasta = $semanaPrimerDia->modify('+6 days')->format('d/m/Y 23:59:59');

                $i++;
                $estadisticaSemanal[] = $turnoRepository->findEstadistica($semanaDesde, ($semanaPrimerDia < $DThasta ? $semanaHasta : $DThasta->format('d/m/Y 23:59:59')), $oficinaId);

                if ($semanaPrimerDia > $DThasta) {
                    break;
                }

            }
        } else {
            $estadisticaSemanal = [];
        }


        //Obtengo Estadística Diaria
        if ($vistaDetallada) {
            //Transforma fecha a objetos DateTime
            $DTdesde = new \DateTime(substr($desde, 6, 4) . '-' . substr($desde,3,2) . '-' . substr($desde, 0,2) . " 00:00:00");
            $DThasta = new \DateTime(substr($hasta,6, 4) . '-' . substr($hasta,3,2) . '-' . substr($hasta, 0,2) . " 23:59:59");

            $diaPrimerDia = $DTdesde;
            $i = 0;
            $estadisticaDiaria = [];
            while (true) {
                if ($i == 0) {
                    $diaDesde = $diaPrimerDia->format('d/m/Y H:i:s');
                } else {
                    $diaDesde = $diaPrimerDia->modify('+1 days')->format('d/m/Y H:i:s');
                }

                $diaHasta = $diaPrimerDia->modify('+0 days')->format('d/m/Y 23:59:59');

                $i++;
                $estadistica = $turnoRepository->findEstadistica($diaDesde, ($diaPrimerDia < $DThasta ? $diaHasta : $DThasta->format('d/m/Y 23:59:59')), $oficinaId);
                if (($estadistica['total'] || $vistaSinTurno) && !$vistaSoloSinTurno) {
                    // Incorporo si existe al menos un turno generado (excluyo feriados y fines de semana) a menos que haya optado por ello
                    $estadisticaDiaria[] =  $estadistica;
                } elseif (!$estadistica['total'] && ($vistaSoloSinTurno)) {
                    $estadisticaDiaria[] =  $estadistica;
                }

                if ($diaPrimerDia > $DThasta) {
                    break;
                }
            }
        } else {
            $estadisticaDiaria = [];
        }

        // Gráfico General
        if ($vistaGeneral) {
            //https://www.gstatic.com/charts/47/css/core/tooltip.css

            $pieChart = new PieChart();
            $pieChart->getData()->setArrayToDataTable(
                [['Estado', 'Cantidad'],
                ['No Atendidos', $estadisticaGeneral['noatendidos']],
                ['Atendidos',  $estadisticaGeneral['atendidos']],
                ['Ausentes', $estadisticaGeneral['noasistidos']],
                ['Rechazados', $estadisticaGeneral['rechazados_libres']],
                ]
            );
            $pieChart->getOptions()->setTitle('Turnos Ocupados');
            $pieChart->getOptions()->setColors(['#33A3A3', '#006600', '#660000', '#BB0000']); 
            $pieChart->getOptions()->setWidth(400);
            $pieChart->getOptions()->setHeight('auto');
            $pieChart->getOptions()->getLegend()->setAlignment('center');
            $pieChart->getOptions()->getTitleTextStyle()->setBold(true);
            $pieChart->getOptions()->getTitleTextStyle()->setColor('#006600');
            $pieChart->getOptions()->getTitleTextStyle()->setItalic(true);
            $pieChart->getOptions()->getTitleTextStyle()->setFontName('Helvetica');
            $pieChart->getOptions()->getTitleTextStyle()->setFontSize(20);
            $pieChart->getOptions()->setIs3D('false');
        }
        else {
            $pieChart = new PieChart();
            $pieChart->getData()->setArrayToDataTable([['Estado', 'Cantidad']]);                
        }

        // Audito la acción
        $logger->info('Se emite informe de estadísticas', [
            'Desde' => substr($desde, 0, 10), 
            'Hasta' => substr($hasta, 0, 10), 
            'Oficina' => $oficina,
            'General' => (count($estadisticaGeneral) ? 'Si' : 'No'),
            'Semanal' => (count($estadisticaSemanal) ? 'Si' : 'No'),
            'Detallada' => (count($estadisticaDiaria) ? 'Si' : 'No'),
            'Usuario' => $this->getUser()->getUsuario()
            ]
        );


        return $this->render('estadistica/show.html.twig', [
            'desde' => substr($desde, 0, 10),
            'hasta' => substr($hasta, 0, 10),
            'oficina' => $oficina,
            'estadisticaGeneral' => $estadisticaGeneral,
            'estadisticaSemanal' => $estadisticaSemanal,
            'estadisticaDiaria' => $estadisticaDiaria,
            'piechart' => $pieChart
        ]);
        
    }

    /**
     * @Route("/estadistica/showEvolucionDiaria", name="estadistica_show_evolucion_diaria", methods={"GET", "POST"})
     */
    public function showEvolucionDiaria(Request $request, OficinaRepository $oficinaRepository, TurnosDiariosRepository $turnosDiariosRepository, LoggerInterface $logger): Response
    {
        // Recibe variables del Formulario
        $desde = $request->request->get('start');
        $hasta = $request->request->get('end');
        $oficinaId = $request->request->get('oficinas');

        if (!isset($oficinaId)) {
            // Busca la oficina a la que pertenece el Usuario
            $oficinaId = $this->getUser()->getOficina()->getId();
            if (!$oficinaId) {
                // Por seguridad, si el usuario no tiene vinculada oficina pre establece "TODAS"
                $oficinaId = 0;
            }
        }

        //Busco Oficina si es necesario para mostrar
        if ($oficinaId) {
            $oficina = $oficinaRepository->findById($oficinaId);
        }
        else {
            $oficina = 'de Todas las Oficinas';
        }

        //Obtengo Estadística Diaria
        $estadistica = $turnosDiariosRepository->findEstadistica($desde, $hasta, $oficinaId);


        $datosGrafico = [['Fecha', 'Cantidad']];
        foreach($estadistica as $dia) {
            $datosGrafico[] = [substr($dia['fecha'],0,5), $dia['cantidad']];
        }

        // Gráfico General
        //https://www.gstatic.com/charts/47/css/core/tooltip.css

        $grafico = new ColumnChart();
        $grafico->getData()->setArrayToDataTable($datosGrafico);

        $grafico->getOptions()->getHAxis()->setTitle('Fecha');
        $grafico->getOptions()->getTitleTextStyle()->setFontName('Helvetica');
        $grafico->getOptions()->getTitleTextStyle()->setFontSize(20);

        $grafico->getOptions()->setTitle('Ocupación de Turnos Diaria');
        $grafico->getOptions()->setOrientation('horizontal');
        $grafico->getOptions()->setHeight(300);
        $grafico->getOptions()->setWidth(500);
        $grafico->getOptions()->setColors(['#060']);
        $grafico->getOptions()->getVAxis()->setTitle('Cantidad');
        $grafico->getOptions()->getLegend()->setPosition('none');


        // Audito la acción
        $logger->info('Se emite informe de estadísticas', [
            'Desde' => substr($desde, 0, 10), 
            'Hasta' => substr($hasta, 0, 10), 
            'Oficina' => $oficina,
            'Usuario' => $this->getUser()->getUsuario()
            ]
        );

        return $this->render('estadistica/showEvolucionDiaria.html.twig', [
            'desde' => substr($desde, 0, 10),
            'hasta' => substr($hasta, 0, 10),
            'oficina' => $oficina,
            'estadistica' => $estadistica,
            'grafico' => $grafico
        ]);
    }


    /**
     * @Route("/estadistica/showOcupacionDiaria", name="informe_show_ocupacion_diaria", methods={"GET", "POST"})
     */
    public function showOcupacionDiaria(Request $request, OficinaRepository $oficinaRepository, TurnoRepository $turnoRepository, LoggerInterface $logger): Response
    {
        // Recibe variables del Formulario
        $desde = $request->request->get('start');
        $tipoInforme = $request->request->get('inlineRadioOptions');
        $orden = $request->request->get('orden');
        $excluirOficinasFD = $request->request->get('chkOficinasFD');       

        if (!$desde) {
            $diaActual = new \DateTime();
            $desde = $diaActual->format('d/m/Y');
        }

        // Obtengo oficinas
        $oficinas = $oficinaRepository->findAllWithUltimoTurno();

        // Obtengo nivel ocupación global de la agenda
        $nivelOcupacionAgendaGlobal = $turnoRepository->findCantidadTurnosAsignados() / $turnoRepository->findCantidadTurnosExistentes();

        // Defino donde retorno los resultados
        $datosOcupacion = [];

        if ($tipoInforme == 1) {
            // Agenda Completa
            $subtitulo = 'Agenda Completa';
            foreach ($oficinas as $ofi) {
                if ( $excluirOficinasFD && stristr($ofi['oficina'], 'firma digital con token')  )
                    continue;

                $cantTurnosOficina = $turnoRepository->findCantidadTurnosExistentes($ofi['id']); 
                $nivelOcupacionAgenda = ($cantTurnosOficina ? $turnoRepository->findCantidadTurnosAsignados($ofi['id']) / $cantTurnosOficina : 0);
                $datosOcupacion[] = ['id' => $ofi['id'], 'oficina' => $ofi['oficina'], 'localidad' => $ofi['localidad'], 'ultimoTurno' => $ofi['ultimoTurno'], 'habilitada' => $ofi['habilitada'], 'ocupacion' => $nivelOcupacionAgenda * 100];
            }
        }

        if ($tipoInforme == 2) {
            // Agenda de un día específico
            $subtitulo = 'Día Consultado: ' . $desde; 

            //Transforma fecha a objetos DateTime
            $DTdesde = new \DateTime(substr($desde, 6, 4) . '-' . substr($desde,3,2) . '-' . substr($desde, 0,2) . " 00:00:00");

            $diaPrimerDia = $DTdesde;
            $diaDesde = $diaPrimerDia->format('d/m/Y H:i:s');
            $diaHasta = $diaPrimerDia->modify('+0 days')->format('d/m/Y 23:59:59');
            foreach ($oficinas as $ofi) {
                if ( $excluirOficinasFD && stristr($ofi['oficina'], 'firma digital con token')  )
                continue;

                $estadistica = $turnoRepository->findEstadistica($diaDesde,$diaHasta, $ofi['id']);
                $datosOcupacion[] = ['id' => $ofi['id'], 'oficina' => $ofi['oficina'], 'localidad' => $ofi['localidad'], 'ultimoTurno' => $ofi['ultimoTurno'], 'habilitada' => $ofi['habilitada'], 
                'desde' => $estadistica['desde'], 'hasta' => $estadistica['hasta'], 'total' => $estadistica['total'], 'otorgados' => $estadistica['otorgados'], 'noatendidos' => $estadistica['noatendidos'], 'atendidos' => $estadistica['atendidos'], 'noasistidos' => $estadistica['noasistidos'], 'rechazados_ocupados' => $estadistica['rechazados_ocupados'], 'rechazados_libres' => $estadistica['rechazados_libres'],                
                'ocupacion' => ($estadistica['total'] > 0 ? $estadistica['otorgados'] / $estadistica['total'] * 100 : 0)];
            }
        }

         // Ordeno los Datos
        if (count($datosOcupacion)) {
            // Obtengo una lista de columnas de los elementos sobre los que quiero ordenar los resultados
            foreach ($datosOcupacion as $clave => $fila) {
                $oficina[$clave] = $fila['oficina'];
                $localidad[$clave] = $fila['localidad'];
                $ocupacion[$clave] = $fila['ocupacion'];
                if ($tipoInforme == 1)  {
                    $ultimoTurno[$clave] = strtotime($fila['ultimoTurno']);
                }
                if ($tipoInforme == 2)  {
                    $total[$clave] =$fila['total'];
                }
            }

            if ($tipoInforme == 1) {
                if ($orden == 1) {
                    // Ordeno los datos por Nivel de Ocupación
                    $subTituloOrden = 'Vista ordenada por Nivel de Ocupación';
                    array_multisort($ocupacion, SORT_NUMERIC, SORT_DESC, $localidad, SORT_ASC, $oficina, SORT_ASC, $ultimoTurno, SORT_NUMERIC, SORT_DESC, $datosOcupacion);
                }
                if ($orden == 2) {
                    // Ordeno los datos por Fecha del Ultimo Turno
                    $subTituloOrden = 'Vista ordenada por Fecha del Ultimo Turno';
                    array_multisort($ultimoTurno, SORT_ASC, $localidad, SORT_ASC, $oficina, SORT_ASC, $ocupacion, SORT_NUMERIC, SORT_DESC, $datosOcupacion);
                }
            }

            if ($tipoInforme == 2) {
                if ($orden == 1) {
                    // Ordeno los datos por Nivel de Ocupación
                    $subTituloOrden = 'Vista ordenada por Nivel de Ocupación';
                    array_multisort($ocupacion, SORT_NUMERIC, SORT_DESC, $localidad, SORT_ASC, $oficina, SORT_ASC, $total, SORT_NUMERIC, SORT_DESC, $datosOcupacion);
                }
                if ($orden == 2) {
                    // Ordeno los datos por Total de Turnos
                    $subTituloOrden = 'Vista ordenada por Total de Turnos';
                    array_multisort($total, SORT_NUMERIC, SORT_DESC, $localidad, SORT_ASC, $oficina, SORT_ASC, $ocupacion, SORT_NUMERIC, SORT_DESC, $datosOcupacion);
                }
            }
        }

        $subTituloExclusion = '';
        if ($excluirOficinasFD) {
            $subTituloExclusion = 'Excluyendo Oficinas de nombre "Firma Digital con Token - Obtención del Certificado"';
        }
    
        // Gráfico General
        //https://www.gstatic.com/charts/47/css/core/tooltip.css
        $datosGrafico = [['Oficina', 'Cantidad']];
        
        foreach($datosOcupacion as $ocupacion) {
            if ($tipoInforme == 1) {
                if ($orden == 1) {
                    $datosGrafico[] = [$ocupacion['oficina'] . '(' . $ocupacion['localidad'] . ')', $ocupacion['ocupacion']];
                }
                if ($orden == 2) {
                    $datosGrafico[] = [$ocupacion['oficina'] . '(' . $ocupacion['localidad'] . ')', $ocupacion['ocupacion']];
                }
            }

            if ($tipoInforme == 2) {
                if ($orden == 1) {
                    $datosGrafico[] = [$ocupacion['oficina'] . '(' . $ocupacion['localidad'] . ')', $ocupacion['ocupacion']];
                }
                if ($orden == 2) {
                    $datosGrafico[] = [$ocupacion['oficina'] . '(' . $ocupacion['localidad'] . ')', $ocupacion['total']];
                }
            }
        }


        $grafico = new ColumnChart();
        $grafico->getData()->setArrayToDataTable($datosGrafico);

        $grafico->getOptions()->getHAxis()->setTitle('');
        $grafico->getOptions()->getHAxis()->setTextPosition('none');
        $grafico->getOptions()->getLegend()->setPosition('none');
        $grafico->getOptions()->getTitleTextStyle()->setFontName('Helvetica');
        $grafico->getOptions()->getTitleTextStyle()->setFontSize(20);

        $grafico->getOptions()->setTitle('Ocupación de Agendas de Turnos');
        $grafico->getOptions()->setOrientation('horizontal');
        $grafico->getOptions()->getChartArea()->setWidth('75%');
        $grafico->getOptions()->setHeight(350);
//        $grafico->getOptions()->setWidth('30%');
        $grafico->getOptions()->setColors(['#060']);
        $grafico->getOptions()->getVAxis()->setMaxValue(100);
        $grafico->getOptions()->getVAxis()->setTitle('Nivel de Ocupación (%)');

        // Audito la acción
        $logger->info('Se emite informe de estadísticas', [
            'Tipo de Informe' => $subtitulo,
            'Fecha' => substr($desde, 0, 10), 
            'Usuario' => $this->getUser()->getUsuario()
            ]
        );

        return $this->render('estadistica/showInformeOcupacionDiaria.html.twig', [
            'desde' => $desde,
            'tipoInforme' => $tipoInforme,
            'orden' => $orden,
            'subtitulo' => $subtitulo,
            'subTituloOrden' => $subTituloOrden,
            'subTituloExclusion' => $subTituloExclusion,
            'ocupacionGlobal' => $nivelOcupacionAgendaGlobal,
            'ocupacion' => $datosOcupacion,
            'grafico' => $grafico
        ]);
    }


    /**
     * @Route("/estadistica/showOcupacionPlena", name="informe_show_ocupacion_plena", methods={"GET", "POST"})
     */
    public function showOcupacionPlena(Request $request, OficinaRepository $oficinaRepository, TurnoRepository $turnoRepository, CircunscripcionRepository $circunscripcionRepository, LoggerInterface $logger): Response
    {
        $circunscripcionID = $request->request->get('circunscripcion');
        $orden = $request->request->get('orden');

        $orderBy = 'a.max DESC, 2, 1';
        $ordenadoPor = 'Fecha Ult. 100%';
        if ($orden == 2) {
            $orderBy = '2, 1, a.max';
            $ordenadoPor = 'Localidad y Organismo';
        }

        $circunscripcion = $circunscripcionRepository->find($circunscripcionID);

        //Obtengo Datos para el Informe
        $ocupacionesPlenas = $oficinaRepository->findMaximasOcupaciones($circunscripcionID, $orderBy);

        // Audito la acción
        $logger->info('Se emite informe de ocupación plena', [
            'Circunscripción' => $circunscripcion,
            'Usuario' => $this->getUser()->getUsuario()
            ]
        );


        return $this->render('estadistica/showInformeOcupacionPlena.html.twig', [
            'circunscripcion' => $circunscripcion,
            'ordenadoPor' => $ordenadoPor,
            'ocupacionesPlenas' => $ocupacionesPlenas
        ]);
        
    }


}

