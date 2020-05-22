<?php

namespace App\Controller;

use App\Entity\Persona;
use App\Entity\Turno;
use App\Entity\TurnosDiarios;
use App\Entity\TurnoRechazado;
use App\Form\PersonaType;
use App\Form\Turno3Type;
use App\Form\Turno4Type;
use App\Form\Turno5Type;
use App\Form\TurnoType;
use App\Form\TurnoRechazarType;
use App\Repository\LocalidadRepository;
use App\Repository\OficinaRepository;
use App\Repository\TurnoRepository;
use App\Repository\TurnosDiariosRepository;
use Knp\Component\Pager\PaginatorInterface;
use DateInterval;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class TurnoController extends AbstractController
{

    /**
     * @Route("/turno", name="turno_index", methods={"GET", "POST"})
     */
    public function index(Request $request, TurnoRepository $turnoRepository, PaginatorInterface $paginator, SessionInterface $session): Response
    {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY'); // Deniega acceso si el usuario no está autenticado (por seguridad)

        // Procesa filtro y lo mantiene en sesión del usuario
        if (is_null($session->get('filtroMomentoTurnos'))) { // Verifica si es la primera vez que ingresa el usuario
            // Establece el primero por defecto (Turnos de Hoy Asignados)
            $filtroMomento = 2;
            $filtroEstado = 1;
            $filtroOficina = '';
        } else {
            if (is_null($request->request->get('filterMoment'))) { // Verifica si ingresa sin indicación de filtro (refresco de la opción de cambio de estado o llamada desde otro lado)
                // Mantiene filtro de estado y momento
                $filtroMomento = $session->get('filtroMomentoTurnos');
                $filtroEstado = $session->get('filtroEstadoTurnos');

                // Analiza el parámetro de Oficina
                if ($request->query->get('cboOficina')) {
                    // La llamada viene por GET (Ej. enlace desde. "enlaestadística/Ocupación de Agenda")
                    $filtroOficina = $request->query->get('cboOficina');
                } else {
                    // Se produjo un cambio desde el panel de acciones. Se recarga la vista con los parámetros de filtro tal como están.                    
                    // Mantiene el filtro actual
                    $filtroOficina = $session->get('filtroOficinaTurnos');
                }
            } else {
                // Activa el filtro seleccionado
                $filtroMomento = $request->request->get('filterMoment');
                $filtroEstado = $request->request->get('filterState');
                $filtroOficina = $request->request->get('cboOficina');
            }
        }
        $session->set('filtroMomentoTurnos', $filtroMomento); // Almacena en session el filtro actual
        $session->set('filtroEstadoTurnos', $filtroEstado); // Almacena en session el filtro actual
        $session->set('filtroOficinaTurnos', $filtroOficina); // Almacena en session el filtro actual

        // Obtiene un arreglo asociativo con valores para las fechas Desde y Hasta que involucra el filtro de momento
        $rango = $this->obtieneMomento($filtroMomento);

        // Procesa filtro de Estado
        switch ($filtroEstado) {
            case 1:
                $estado = 1; // No atendidos
                break;
            case 2:
                $estado = 2; // Atendidos
                break;
            case 9:
                $estado = 9; // Todos
                break;
        }
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_AUDITORIA_GESTION')) {
            // Busca los turnos en función a los estados de todas las oficinas
            if ($filtroOficina) {
                $turnosOtorgados = $pagination = $paginator->paginate($turnoRepository->findWithRoleUser($rango, $estado, $filtroOficina), $request->query->getInt('page', 1), 100);
            } else {
                $turnosOtorgados = $pagination = $paginator->paginate($turnoRepository->findByRoleAdmin($rango, $estado), $request->query->getInt('page', 1), 100);
            }
        } else {
            if ($this->isGranted('ROLE_USER')) {
                // Busca los turnos en función a los estados de la oficina a la que pertenece el usuario
                $oficinaUsuario = $this->getUser()->getOficina();
                $turnosOtorgados = $pagination = $paginator->paginate($turnoRepository->findWithRoleUser($rango, $estado, $oficinaUsuario), $request->query->getInt('page', 1), 100);
            }
        }

        return $this->render('turno/index.html.twig', [
            'filtroMomento' => $filtroMomento,
            'filtroEstado' => $filtroEstado,
            'filtroOficina' => $filtroOficina,
            'turnos' => $turnosOtorgados,
        ]);
    }

    // Alta generada automaticámente. No se utilizará pero no se quiso borrar el método por las dudas
    /**
     * @Route("/turno/new", name="turno_new", methods={"GET","POST"})
     */
    function new(Request $request, LocalidadRepository $localidadRepository): Response
    {
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

        $turno = new Turno();
        $form = $this->createForm(TurnoType::class, $turno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
        }

        return $this->render('turno/new.html.twig', [
            'turno' => $turno,
            'form' => $form->createView(),
        ]);
    }

    // Wizard 1/4: Datos del Solicitante
    /**
     * @Route("/TurnosWeb/solicitante", name="turno_new2", methods={"GET","POST"})
     */
    public function new2(Request $request, SessionInterface $session): Response
    {
        $session->start();

        $persona = new Persona();
        $form = $this->createForm(PersonaType::class, $persona);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // El nombre y apellido de la persona los fuerzo en mayusculas
            $persona->setApellido(mb_strtoupper($persona->getApellido()));
            $persona->setNombre(mb_strtoupper($persona->getNombre()));
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($persona);
            $session->set('persona', $persona);
            return $this->redirectToRoute('turno_new3');
        }

        return $this->render('persona/new.html.twig', [
            'persona' => $persona,
            'form' => $form->createView(),
        ]);
    }

    // Wizard 2/4: Selección de Organismo
    /**
     * @Route("/TurnosWeb/oficina", name="turno_new3", methods={"GET","POST"})
     */
    public function new3(SessionInterface $session, Request $request): Response
    {
        $persona = $session->get('persona');

        // Si viene sin instancia de persona lo redirige al paso de selección de persona
        if (!$persona) {
            return $this->redirectToRoute('turno_new2');
        }

        $turno = new Turno();
        $turno->setPersona($persona);
        $form = $this->createForm(Turno3Type::class, $turno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($turno);
            $session->set('turno', $turno);
            return $this->redirectToRoute('turno_new4');
        }

        return $this->render('turno/new3.html.twig', [
            'turno' => $turno,
            'persona' => $persona,
            'form' => $form->createView(),
        ]);
    }

    // Wizard 3/4: Selección de Fecha y Hora
    /**
     * @Route("/TurnosWeb/fechaHora", name="turno_new4", methods={"GET","POST"})
     */
    public function new4(SessionInterface $session, Request $request, TurnoRepository $turnoRepository): Response
    {
        $persona = $session->get('persona');
        $turno = $session->get('turno');

        // Si viene sin instancia de persona lo redirige al paso de selección de persona
        if (!$persona) {
            return $this->redirectToRoute('turno_new2');
        }
        // Si viene sin instancia de turno o sin oficina seleccionada lo redirige al paso de selección de oficina
        if (!$turno || !$turno->getOficina()) {
            return $this->redirectToRoute('turno_new3');
        }

        $oficinaId = $turno->getOficina()->getId();
        $primerDiaDisponible = $turnoRepository->findPrimerDiaDisponibleByOficina($oficinaId);
        $ultimoDiaDisponible = $turnoRepository->findUltimoDiaDisponibleByOficina($oficinaId);

        $form = $this->createForm(Turno4Type::class, $turno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($turno);
            $entityManager->persist($persona);

            $session->set('turno', $persona);
            $session->set('turno', $turno);

            return $this->redirectToRoute('turno_new5');
        }

        return $this->render('turno/new4.html.twig', [
            'turno' => $turno,
            'persona' => $persona,
            'oficinaID' => $oficinaId,
            'primerDiaDisponible' => $primerDiaDisponible,
            'ultimoDiaDisponible' => $ultimoDiaDisponible,
            'form' => $form->createView(),
        ]);
    }

    // Wizard 4/4: Confirmación del Turno
    /**
     * @Route("/TurnosWeb/confirmacion", name="turno_new5", methods={"GET","POST"})
     */
    public function new5(SessionInterface $session, Request $request, TurnoRepository $turnoRepository, TurnosDiariosRepository $turnosDiariosRepository, LoggerInterface $logger): Response
    {
        $persona = $session->get('persona');
        $turno = $session->get('turno');

        // Si viene sin instancia de persona lo redirige al paso de selección de persona
        if (!$persona) {
            return $this->redirectToRoute('turno_new2');
        }
        // Si viene sin instancia de turno o sin oficina seleccionada lo redirige al paso de selección de oficina
        if (!$turno || !$turno->getOficina()) {
            return $this->redirectToRoute('turno_new3');
        }
        // Si viene sin fecha y hora seleccionada lo redirige al paso de seleccción de fecha y hora
        if (!$turno->getFechaHora()) {
            return $this->redirectToRoute('turno_new4');
        }

        $form = $this->createForm(Turno5Type::class, $turno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $turnoActualizar = $turnoRepository->findTurnoLibre($turno->getOficina()->getId(), $turno->getFechaHora());

            // Verifico si el turno no se ocupó
            // OJO que si la concurrencia es alta este control no es infalible!
            // Entre el find() y el flush() hay un marco microtemporal
            // En caso de fallar el control, el primero en grabar será sobreescrito por el segundo.
            // El primero recibió notificación del turno por correo pero la Oficina no lo va a tener registrado.
            if (!$turnoActualizar) {
                // Turno Ocupado
                return $this->redirectToRoute('turnoOcupado');
            } else {
                // Turno Libre. Grabo.
                $turnoActualizar->setMotivo($turno->getMotivo());
                $turnoActualizar->setPersona($persona);

                // Cuento turnos que se ocupan por día (con propósitos estadísticos)
                $cuentoTurnosdelDia = $turnosDiariosRepository->findByOficinaByFecha($turnoActualizar->getOficina(), date('d/m/Y'));
                if ($cuentoTurnosdelDia) {
                    // Acumulo 
                    $cuentoTurnosdelDia->setCantidad($cuentoTurnosdelDia->getCantidad() + 1);
                } else {
                    // Primer turno del día
                    $cuentoTurnosdelDia = new TurnosDiarios();
                    $cuentoTurnosdelDia->setOficina($turnoActualizar->getOficina());
                    $cuentoTurnosdelDia->setFecha(new \DateTime(date("Y-m-d")));
                    $cuentoTurnosdelDia->setCantidad(1);
                }

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->merge($turnoActualizar);
                $entityManager->persist($persona);
                $entityManager->persist($cuentoTurnosdelDia);
                $entityManager->flush();
                $this->addFlash('success', 'Su turno ha sido otorgado satisfactoriamente');
                $logger->info(
                    'Turno Otorgado',
                    [
                        'Oficina' => $turnoActualizar->getOficina()->getOficinayLocalidad(),
                        'Turno' => $turnoActualizar->getTurno(),
                        'Solicitante' => $turnoActualizar->getPersona()->getPersona()
                    ]
                );
            }

            return $this->redirectToRoute('emailConfirmacion');
        }

        return $this->render('turno/new5.html.twig', [
            'turno' => $turno,
            'persona' => $persona,
            'form' => $form->createView(),
        ]);
    }

    // Wizard 4/4: Notificación de Turno Ocupado
    /**
     * @Route("/TurnosWeb/turnoOcupado", name="turnoOcupado", methods={"GET","POST"})
     */
    public function turnoOcupado(SessionInterface $session, Request $request, TurnoRepository $turnoRepository, LoggerInterface $logger): Response
    {
        $persona = $session->get('persona');
        $turno = $session->get('turno');

        $form = $this->createForm(Turno5Type::class, $turno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Notifica que el turno se ocupó y lo redirige a seleccionar otra fecha/hora
            $logger->info(
                'Turno Ocupado',
                [
                    'Oficina' => $turno->getOficina()->getOficinayLocalidad(),
                    'Turno' => $turno->getTurno(),
                    'Solicitante' => $turno->getPersona()->getPersona()
                ]
            );
            return $this->redirectToRoute('turno_new4');
        }

        return $this->render('turno/turnoOcupado.html.twig', [
            'turno' => $turno,
            'persona' => $persona,
            'form' => $form->createView(),
        ]);
    }

    // Notificación por correo del Turno
    /**
     * @Route("/TurnosWeb/notificacion", name="emailConfirmacion", methods={"GET","POST"})
     */
    public function sendEmail(SessionInterface $session, MailerInterface $mailer, LoggerInterface $logger)
    {
        $turno = $session->get('turno');

        // Si la persona ingresó un correo, envía una notificación con los datos del turno
        if ($turno->getPersona()->getEmail()) {
            $fromAdrress = $_ENV['MAIL_FROM'];
            $email = (new TemplatedEmail())
                ->from($fromAdrress)
                ->to($turno->getPersona()->getEmail())
                //                ->addBcc('mmaglianesi@justiciasantafe.gov.ar')
                //                ->addBcc('jialarcon@justiciasantafe.gov.ar')
                ->subject('Poder Judicial Santa Fe - Confirmación de Turno')

                // path of the Twig template to render
                ->htmlTemplate('turno/new6.html.twig')

                // pass variables (name => value) to the template
                ->context([
                    'expiration_date' => new \DateTime('+7 days'),
                    'username' => 'foo',
                    'turno' => $turno,
                ]);
            $mailer->send($email);
            $this->addFlash('info', 'Se ha enviado un correo a la dirección ' . $turno->getPersona()->getEmail());
            $logger->info(
                'Notificación Enviada',
                [
                    'Destinatario' => $turno->getPersona()->getPersona(),
                    'Dirección' => $turno->getPersona()->getEmail()
                ]
            );
        }

        return $this->redirectToRoute('comprobanteTurno');
    }

    // Comprobante del Turno
    /**
     * @Route("/TurnosWeb/comprobante", name="comprobanteTurno", methods={"GET","POST"})
     */
    public function comprobanteTurno(Request $request, SessionInterface $session)
    {
        $turno = $session->get('turno');

        $form = $this->createForm(Turno5Type::class, $turno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Finalizó el proceso de Solicitud de Turnos. Vuelve a la página principal.
            return $this->redirectToRoute('main');
        }

        return $this->render('turno/comprobanteTurno.html.twig', [
            'turno' => $turno,
            'form' => $form->createView(),
        ]);


        // Limpio las variables de session utilizadas
        $session->remove('turno');
        $session->remove('persona');
    }

    /**
     * @Route("/turno/{id}", name="turno_show", methods={"GET"})
     */
    public function show(Turno $turno): Response
    {
        return $this->render('turno/show.html.twig', [
            'turno' => $turno,
        ]);
    }

    /**
     * @Route("/turno/{id}/edit", name="turno_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Turno $turno): Response
    {
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

        $form = $this->createForm(TurnoType::class, $turno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Se han guardado los cambios');

            return $this->redirectToRoute('turno_index');
        }

        return $this->render('turno/edit.html.twig', [
            'turno' => $turno,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/turno/{id}/atendido", name="turno_atendido", methods={"GET","POST"})
     */
    public function atendido(Turno $turno, LoggerInterface $logger): Response
    {
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

        if ($turno->getEstado() == 1 || $turno->getEstado() == 2) {
            // Alterna estado de Atendido (de No Atendido (1) a Atendido (2) o de Atendido (2) a No Atendido (1))
            $turno->setEstado(($turno->getEstado() % 2) + 1);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', ($turno->getEstado() == 2 ? 'El turno se ha marcado como Atendido' : 'El turno se marcado como No Atendido'));
            $logger->info(($turno->getEstado() == 2 ? 'Marca como Atendido' : 'Marca como No Atendido'),
                [
                    'Oficina' => $turno->getOficina()->getOficinayLocalidad(),
                    'Turno' => $turno->getTurno(),
                    'Persona' => $turno->getPersona()->getPersona(),
                    'Usuario' => $this->getUser()->getUsuario()
                ]
            );

            return $this->redirectToRoute('turno_index');
        }
    }

    /**
     * @Route("/turno/{id}", name="turno_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Turno $turno, LoggerInterface $logger): Response
    {
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

        if ($this->isCsrfTokenValid('delete' . $turno->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($turno);
            $this->addFlash('danger', 'Se ha borrado el turno');
            $logger->info(
                'Turno Borrado',
                [
                    'Oficina' => $turno->getOficina()->getOficinayLocalidad(),
                    'Turno' => $turno->getTurno(),
                    'Usuario' => $this->getUser()->getUsuario()
                ]
            );
            $entityManager->flush();
        }

        return $this->redirectToRoute('turno_index');
    }

    /**
     * @Route("/turno/{id}/noAsistido", name="turno_no_asistido", methods={"GET","POST"})
     */
    public function no_asistido(Turno $turno, LoggerInterface $logger): Response
    {
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

        if ($turno->getEstado() == 1) {
            // Marca el turno como Ausente
            $turno->setEstado(3); // Rechazado
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'El turno se ha marcado como Ausente');
            $logger->info(('Marca como Ausente'),
                [
                    'Oficina' => $turno->getOficina()->getOficinayLocalidad(),
                    'Turno' => $turno->getTurno(),
                    'Persona' => $turno->getPersona()->getPersona(),
                    'Usuario' => $this->getUser()->getUsuario()
                ]
            );
            return $this->redirectToRoute('turno_index');
        }
    }

    /**
     * @Route("/turno/{id}/rechazado", name="turno_rechazado", methods={"GET","POST"})
     */
    public function rechazado(Request $request, Turno $turno, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        // Deniega acceso si no tiene un rol de editor o superior
        $this->denyAccessUnlessGranted('ROLE_EDITOR');

        if ($turno->getEstado() == 1) {

            $motivoRechazo = $_ENV['MOTIVO_RECHAZO'];

            $form = $this->createForm(TurnoRechazarType::class, $turno);
            $form->get('motivoRechazo')->setData($motivoRechazo);
            $form->get('enviarMail')->setData(false);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $motivoRechazo = $request->request->get('turno_rechazar')['motivoRechazo'];

                // Envia correo notificando el Rechazo
                if (isset($request->request->get('turno_rechazar')['enviarMail'])) {
                    $fromAdrress = $_ENV['MAIL_FROM'];
                    $email = (new TemplatedEmail())
                        ->from($fromAdrress)
                        ->to($turno->getPersona()->getEmail())
                        //                        ->addBcc('mmaglianesi@justiciasantafe.gov.ar')
                        //                        ->addBcc('jialarcon@justiciasantafe.gov.ar')
                        ->subject('Poder Judicial Santa Fe - Solicitud de Turno Cancelada')
                        ->htmlTemplate('turno/correoTurnoRechazado.html.twig')
                        ->context([
                            'expiration_date' => new \DateTime('+7 days'),
                            'turno' => $turno,
                            'motivoRechazo' => $motivoRechazo
                        ]);
                    $mailer->send($email);
                    $logger->info(
                        'Notificación de Rechazo Enviada',
                        [
                            'Destinatario' => $turno->getPersona()->getPersona(),
                            'Dirección' => $turno->getPersona()->getEmail(),
                            'Motivo Indicado' => $motivoRechazo
                        ]
                    );
                }

                // Rechaza el turno liberándolo para que otra persona lo pueda tomar
                $this->addFlash('warning', 'Se ha rechazado el turno');
                $logger->info(('Marca como Rechazado'),
                    [
                        'Oficina' => $turno->getOficina()->getOficinayLocalidad(),
                        'Turno' => $turno->getTurno(),
                        'Persona' => $turno->getPersona()->getPersona(),
                        'Usuario' => $this->getUser()->getUsuario()
                    ]
                );

                // Almacena datos del rechazo
                $turnoRechazado = new TurnoRechazado();
                $turnoRechazado->setFechaHoraRechazo(new \DateTime(date("Y-m-d H:i:s")));
                $turnoRechazado->setFechaHoraTurno($turno->getFechaHora());
                $turnoRechazado->setMotivo($turnoRechazado->getMotivo());
                $turnoRechazado->setOficina($turno->getOficina());
                $turnoRechazado->setPersona($turno->getPersona());
                $turnoRechazado->setEmailEnviado(isset($request->request->get('turno_rechazar')['enviarMail']));
                $turnoRechazado->setMotivoRechazo($motivoRechazo);

                // Libero el turno
                $turno->setEstado(1);
                $turno->setPersona(null);
                $turno->setMotivo('');

                // Grabo
                $this->getDoctrine()->getManager()->persist($turnoRechazado);
                $this->getDoctrine()->getManager()->flush();

                return $this->redirectToRoute('turno_index');
            }

            return $this->render('turno/rechazar.html.twig', [
                'turno' => $turno,
                'form' => $form->createView(),
            ]);
        }
    }

    /**
     * @Route("/TurnosWeb/localidad_circunscripcion/{circunscripcion_id}", name="localidad_by_circunscripcion", requirements = {"circunscripcion_id" = "\d+"}, methods={"GET", "POST"})
     */
    public function localidadByCircunscripcion($circunscripcion_id, LocalidadRepository $localidadRepository)
    {
        $localidades = $localidadRepository->findLocalidadesByCircunscripcion($circunscripcion_id);
        return new JsonResponse($localidades);
    }


    /**
     * @Route("/TurnosWeb/oficina_localidad/{localidad_id}", name="oficinas_by_localidad", requirements = {"localidad_id" = "\d+"}, methods={"GET", "POST"})
     */
    public function oficinasByLocalidad($localidad_id, OficinaRepository $oficinaRepository)
    {
        $oficinas = $oficinaRepository->findOficinasHabilitadasByLocalidad($localidad_id);
        return new JsonResponse($oficinas);
    }

    /**
     * @Route("/TurnosWeb/oficinas", name="oficinas", methods={"GET", "POST"})
     */
    public function oficinas(OficinaRepository $oficinaRepository)
    {
        $oficinas = $oficinaRepository->findAllOficinas();
        return new JsonResponse($oficinas);
    }

    /**
     * @Route("/TurnosWeb/turnoslibres_oficina/{oficina_id}", name="turnoslibres_by_localidad", requirements = {"oficina_id" = "\d+"}, methods={"POST"})
     */
    public function diasLibresByOficina(TurnoRepository $turnoRepository, $oficina_id)
    {
        $turnosLibres = $turnoRepository->findDiasDisponiblesByOficina($oficina_id);
        return new JsonResponse($turnosLibres);
    }

    /**
     * @Route("/TurnosWeb/diasOcupadosOficina/{oficina_id}", name="diasOcupadosOficina", requirements = {"oficina_id" = "\d+"}, methods={"GET", "POST"})
     */
    public function diasOcupadosByOficina(TurnoRepository $turnoRepository, SessionInterface $session, $oficina_id)
    {
        // Este proceso recorre día a día el rango de días posibles de turnos para una oficina y retorna
        // un arreglo de los días que no tienen ningún turno libre o que no tienen turnos generados (feriados)

        // Obtiene la Oficina
        $turno = $session->get('turno');
        $oficinaId = $turno->getOficina()->getId();

        // Obtiene el primer turno a partir del momento actual
        $primerDiaDisponible = $turnoRepository->findPrimerDiaDisponibleByOficina($oficinaId);

        // Obtiene el último turno disponible para la oficina
        $ultimoDiaDisponible = $turnoRepository->findUltimoDiaDisponibleByOficina($oficinaId);

        // Estable rangos temporales desde el primer día al último
        $desde = (new \DateTime)->createFromFormat('d/m/Y H:i:s', $primerDiaDisponible . '00:00:00');
        $hasta = (new \DateTime)->createFromFormat('d/m/Y H:i:s', $ultimoDiaDisponible . '23:59:59');

        // Recorre cada uno de los días y arma en $diasNoHabilitados los días que no tienen turnos libres
        // o bien, los turnos que no tienen turnos creados (feriados). Chequeo tambien que $desde y $hasta tengan valores
        $diasNoHabilitados = [];
        while (true && ($desde && $hasta)) {
            // Establece horaría máximo de búsqueda. Se busca desde las 0hs hasta las 23:59, día a día
            $horaHasta = (new \DateTime)->createFromFormat('d/m/Y H:i:s', $desde->format('d/m/Y') . ' 23:59:59');

            // OJO con este método. Debería retornar sólo si existen o no turnos y retorna todos los turnos.
            // TODO Mejorarlo por una cuestión de performance y de recursos
            $horarios = $turnoRepository->findExisteTurnoLibreByOficinaByFecha($oficinaId, $desde, $horaHasta);

            // Si no existen turnos libres para ese día (o bien, no existen turnos creados)
            if (!$horarios) {
                // Lo almacena como día no habiltiado
                $diasNoHabilitados[] = $desde->format('d/m/Y');
            }

            // Incrementa el intervalo en un día
            $desde->add(new DateInterval('P1D'));
            if ($desde >= $hasta) {
                break;
            }
        }

        return new JsonResponse($diasNoHabilitados);
    }

    /**
     * @Route("/TurnosWeb/horariosDisponiblesOficinaFecha/{oficina_id}/{fecha}", name="horarisDisponibles", methods={"POST"})
     */
    public function horariosDisponiblesByOficinaByFecha(TurnoRepository $turnoRepository, $oficina_id, $fecha)
    {
        $horariosDisponibles = $turnoRepository->findHorariosDisponiblesByOficinaByFecha($oficina_id, $fecha);
        return new JsonResponse($horariosDisponibles);
    }

    /**
     * @Route("/TurnosWeb/ocupacionAgenda", name="ocupacionAgenda", methods={"GET", "POST"})
     */
    public function ocupacionAgenda(Request $request, TurnoRepository $turnoRepository)
    {
        $nivelOcupacionAgenda = 0;
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_AUDITORIA_GESTION')) {
            $filtroOficina = $request->request->get('oficina_id');
            $nivelOcupacionAgenda = $turnoRepository->findCantidadTurnosAsignados($filtroOficina) / $turnoRepository->findCantidadTurnosExistentes($filtroOficina);
        } else {
            if ($this->isGranted('ROLE_USER')) {
                // Busca en la oficina a la que pertenece el usuario
                $oficinaUsuario = $this->getUser()->getOficina()->getId();
                $nivelOcupacionAgenda = $turnoRepository->findCantidadTurnosAsignados($oficinaUsuario) / $turnoRepository->findCantidadTurnosExistentes($oficinaUsuario);
            }
        }

        return new JsonResponse(round($nivelOcupacionAgenda * 100));
    }


    private function obtieneMomento($momento)
    {
        $rango = [];
        switch ($momento) {
            case 1: // Pasado (desde el 01/01/1970 al día anterior al actual)
                $rango['desde'] = new \DateTime("1970-01-01 00:00:00");
                $rango['hasta'] = (new \DateTime(date("Y-m-d") . " 23:59:59"))
                    ->sub(new DateInterval('P1D')); // Resta un día al día actual
                break;
            case 2: // Hoy (de las 0hs a las 23:59 del día actual)
                $rango['desde'] = new \DateTime(date("Y-m-d") . " 00:00:00");
                $rango['hasta'] = new \DateTime(date("Y-m-d") . " 23:59:59");
                break;
            case 3: // Futuro (del posterior al día actual hasta el 31/12/2200)
                $rango['desde'] = (new \DateTime(date("Y-m-d") . " 00:00:00"))
                    ->add(new DateInterval('P1D')); // Suma un día al día actual
                $rango['hasta'] = new \DateTime("2200-12-31 23:59:59");
                break;
        }
        return $rango;
    }
}
