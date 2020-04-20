<?php

namespace App\Controller;

use App\Entity\Persona;
use App\Entity\Turno;
use App\Form\PersonaType;
use App\Form\Turno3Type;
use App\Form\Turno4Type;
use App\Form\Turno5Type;
use App\Form\TurnoType;
use App\Repository\LocalidadRepository;
use App\Repository\OficinaRepository;
use App\Repository\TurnoRepository;
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

class TurnoController extends AbstractController
{

    /**
     * @Route("/turno", name="turno_index", methods={"GET", "POST"})
     */
    public function index(Request $request, TurnoRepository $turnoRepository, SessionInterface $session): Response
    {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY'); // Deniega acceso si el usuario no está autenticado (por seguridad)

        // Procesa filtro y lo mantiene en sesión del usuario
        if (is_null($session->get('filtroMomentoTurnos'))) { // Verifica si es la primera vez que ingresa el usuario
            // Establece el primero por defecto (Turnos de Hoy Asignados)
            $filtroMomento = 2;
            $filtroEstado = 1;
        } else {
            if (is_null($request->request->get('filterMoment'))) { // Verifica si ingresa sin indicación de filtro (refresco de la opción atendido)
                // Mantiene el filtro actual
                $filtroMomento = $session->get('filtroMomentoTurnos');
                $filtroEstado = $session->get('filtroEstadoTurnos');
            } else {
                // Activa el filtro seleccionado
                $filtroMomento = $request->request->get('filterMoment');
                $filtroEstado = $request->request->get('filterState');
            }
        }
        $session->set('filtroMomentoTurnos', $filtroMomento); // Almacena en session el filtro actual
        $session->set('filtroEstadoTurnos', $filtroEstado); // Almacena en session el filtro actual

        // Obtiene un arreglo asociativo con valores para las fechas Desde y Hasta que involucra el filtro de momento
        $rango = $this->obtieneMomento($filtroMomento);

        // Procesa filtro de Estado
        switch ($filtroEstado) {
            case 1:
                $atendido = 'false';
                break;
            case 2:
                $atendido = 'true';
                break;
            case 3:
                $atendido = 'TODOS';
                break;
        }

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_AUDITORIA_GESTION')) {
            // Busca los turnos en función a los estados de todas las oficinas
            $turnosOtorgados = $turnoRepository->findByRoleAdmin($rango, $atendido);
        } else {
            if ($this->isGranted('ROLE_USER')) {
                // Busca los turnos en función a los estados de la oficina a la que pertenece el usuario
                $oficinaUsuario = $this->getUser()->getOficina();
                $turnosOtorgados = $turnoRepository->findWithRoleUser($rango, $atendido, $oficinaUsuario);
            }
        }

        return $this->render('turno/index.html.twig', [
            'filtroMomento' => $filtroMomento,
            'filtroEstado' => $filtroEstado,
            'turnos' => $turnosOtorgados,
        ]);

    }

    // Alta generada automaticámente. No se utilizará pero no se quiso borrar el método por las dudas
    /**
     * @Route("/turno/new", name="turno_new", methods={"GET","POST"})
     */
    function new (Request $request, LocalidadRepository $localidadRepository): Response {
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
     * @IsGranted("ROLE_USER")
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
     * @IsGranted("ROLE_USER")
     */
    public function new3(SessionInterface $session, Request $request): Response
    {
        $persona = $session->get('persona');
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
     * @IsGranted("ROLE_USER")
     */
    public function new4(SessionInterface $session, Request $request, TurnoRepository $turnoRepository): Response
    {
        $persona = $session->get('persona');
        $turno = $session->get('turno');

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
     * @IsGranted("ROLE_USER")
     */
    public function new5(SessionInterface $session, Request $request, TurnoRepository $turnoRepository): Response
    {
        $persona = $session->get('persona');
        $turno = $session->get('turno');

        $form = $this->createForm(Turno5Type::class, $turno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $turnoActualizar = $turnoRepository->findTurno($turno->getOficina()->getId(), $turno->getFechaHora());

            // Verifico si el turno no se ocupó
            // OJO que si la concurrencia es alta este control no es infalible!
            // Entre el find() y el flush() hay un marco microtemporal
            // En caso de fallar el control, el primero en grabar será sobreescrito por el segundo.
            // El primero recibió notificación del turno por correo pero la Oficina no lo va a tener registrado.
            if ($turnoActualizar->getPersona()) {
                // Turno Ocupado
                return $this->redirectToRoute('turnoOcupado');
            } else {
                // Turno Libre. Grabo.
                $turnoActualizar->setMotivo($turno->getMotivo());
                $turnoActualizar->setPersona($persona);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->merge($turnoActualizar);
                $entityManager->persist($persona);
                $entityManager->flush();
                $this->addFlash('success', 'Su turno ha sido otorgado satisfactoriamente');                
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
     * @IsGranted("ROLE_USER")
     */
    public function turnoOcupado(SessionInterface $session, Request $request, TurnoRepository $turnoRepository): Response
    {
        $persona = $session->get('persona');
        $turno = $session->get('turno');

        $form = $this->createForm(Turno5Type::class, $turno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Notifica que el turno se ocupó y lo redirige a seleccionar otra fecha/hora
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
     * @IsGranted("ROLE_USER")
     */
    public function sendEmail(SessionInterface $session, MailerInterface $mailer)
    {
        $turno = $session->get('turno');

        // Si la persona ingresó un correo, envía una notificación con los datos del turno
        if ($turno->getPersona()->getEmail()) {
            $fromAdrress = $_ENV['MAIL_FROM'];
            $email = (new TemplatedEmail())
                ->from($fromAdrress)
                ->to($turno->getPersona()->getEmail())
                ->addBcc('mmaglianesi@justiciasantafe.gov.ar')
                ->addBcc('jialarcon@justiciasantafe.gov.ar')
                ->subject('Poder Judicial Santa Fe - Confirmación de Turno')

                // path of the Twig template to render
                ->htmlTemplate('turno/new6.html.twig')

                // pass variables (name => value) to the template
                ->context([
                    'expiration_date' => new \DateTime('+7 days'),
                    'username' => 'foo',
                    'turno' => $turno,
                ])
            ;
            $mailer->send($email);
            $this->addFlash('info', 'Se ha enviado un correo a la dirección ' . $turno->getPersona()->getEmail());

        }

        return $this->redirectToRoute('comprobanteTurno');

    }

    // NotiComprobante del Turno
    /**
     * @Route("/TurnosWeb/comprobante", name="comprobanteTurno", methods={"GET","POST"})
     * @IsGranted("ROLE_USER")
     */
    public function comprobanteTurno(Request $request, SessionInterface $session)
    {
        $turno = $session->get('turno');

        $form = $this->createForm(Turno5Type::class, $turno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Finalizó el proceso de Solicitud de Turnos. Vuelve a la página principal.
            return $this->redirectToRoute('mainTMP');
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
        $form = $this->createForm(TurnoType::class, $turno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

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
    public function atendido(Request $request, Turno $turno): Response
    {
        // Alterna estado de Atendido (de true -> false o de false -> true)
        $turno->setAtendido(!$turno->getAtendido());
        $this->getDoctrine()->getManager()->flush();
        return $this->redirectToRoute('turno_index');

    }

    /**
     * @Route("/turno/{id}", name="turno_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Turno $turno): Response
    {
        if ($this->isCsrfTokenValid('delete' . $turno->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($turno);
            $entityManager->flush();
        }

        return $this->redirectToRoute('turno_index');
    }

    /**
     * @Route("/TurnosWeb/oficina_localidad/{localidad_id}", name="oficinas_by_localidad", requirements = {"localidad_id" = "\d+"}, methods={"POST"})
     * @IsGranted("ROLE_USER")
     */
    public function oficinasByLocalidad($localidad_id, OficinaRepository $oficinaRepository)
    {
        $em = $this->getDoctrine()->getManager();
        $oficinas = $oficinaRepository->findOficinaByLocalidad($localidad_id);
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
        // o bien, los turnos que no tienen turnos creados (feriados)
        $diasNoHabilitados = [];
        while (true) {
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
