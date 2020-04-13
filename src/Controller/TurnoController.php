<?php

namespace App\Controller;

use App\Entity\Turno;
use App\Form\TurnoType;
use App\Form\Turno3Type;
use App\Form\Turno4Type;
use App\Form\Turno5Type;
use App\Repository\TurnoRepository;
use App\Entity\Persona;
use App\Form\PersonaType;
use App\Repository\LocalidadRepository;
use App\Repository\OficinaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class TurnoController extends AbstractController
{
    /**
     * @Route("/turno", name="turno_index", methods={"GET"})
     */
    public function index(Request $request, TurnoRepository $turnoRepository, $filtro=1): Response
    {

        if (is_null($request->query->get('filter'))) {
            $filtro = 1;
        } else {
            $filtro = $request->query->get('filter');
        }

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($this->isGranted('ROLE_ADMIN')) {
            if ($filtro) {
                $turnosOtorgados = $turnoRepository->findAllOtorgados();
            } else {
                $turnosOtorgados = $turnoRepository->findAll();
            }

        } else {
            if ($this->isGranted('ROLE_USER')) {
                $oficinaUsuario = $this->getUser()->getOficina();
                if ($filtro) {
                    $turnosOtorgados = $turnoRepository->findAllOtorgadosByOficina($oficinaUsuario);
                } else {
                    $turnosOtorgados = $turnoRepository->findAllByOficina($oficinaUsuario);
                }
            }
        }

        return $this->render('turno/index.html.twig', [
            'filtro' => $filtro,
            'turnos' => $turnosOtorgados,
        ]);
    }

    // Alta generada automaticámente. No se utilizará pero no se quiso borrar el método por las dudas
    /**
     * @Route("/turno/new", name="turno_new", methods={"GET","POST"})
     */
    public function new(Request $request, LocalidadRepository $localidadRepository): Response
    {
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

        $oficinaId = $turno->getOficina()->getId();
        $diaActual = date('d/m/Y');      
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
            'diaActual' => $diaActual,
            'ultimoDiaDisponible' => $ultimoDiaDisponible,
            'form' => $form->createView(),
        ]);
    }

    // Wizard 4/4: Confirmación del Turno
    /**
     * @Route("/TurnosWeb/confirmacion", name="turno_new5", methods={"GET","POST"})
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
     */
    public function sendEmail(SessionInterface $session, MailerInterface $mailer)
    {
        $persona = $session->get('persona');
        $turno = $session->get('turno');

        $email = (new TemplatedEmail())
        ->from('maglianesi@gmail.com')
        ->to('maglianesi@yahoo.com.ar')
        ->addTo('mmaglianesi@justiciasantafe.gov.ar')
        ->subject('Poder Judicial Santa Fe - Confirmación de Turno')
    
        // path of the Twig template to render
        ->htmlTemplate('turno/new6.html.twig')
    
        // pass variables (name => value) to the template
        ->context([
            'expiration_date' => new \DateTime('+7 days'),
            'username' => 'foo',
            'turno' => $turno,
            'persona' => $persona,
        ])
    ;        

        //TODO Si persona no se utiliza en el correo no meterla dentro del context()
        $mailer->send($email);

        //TODO ver como borrar de session las variables utilizadas (persona, turno)

        return $this->redirectToRoute('main');
        
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
     * @Route("/turno/{id}", name="turno_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Turno $turno): Response
    {
        if ($this->isCsrfTokenValid('delete'.$turno->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($turno);
            $entityManager->flush();
        }

        return $this->redirectToRoute('turno_index');
    }

    /**
     * @Route("/TurnosWeb/oficina_localidad/{localidad_id}", name="oficinas_by_localidad", requirements = {"localidad_id" = "\d+"}, methods={"POST"})
     */
    public function oficinasByLocalidad($localidad_id, OficinaRepository $oficinaRepository) {
        $em = $this->getDoctrine()->getManager();
        $oficinas = $oficinaRepository->findOficinaByLocalidad($localidad_id);
        return new JsonResponse($oficinas);
    }

    /**
     * @Route("/TurnosWeb/turnoslibres_oficina/{oficina_id}", name="turnoslibres_by_localidad", requirements = {"oficina_id" = "\d+"}, methods={"POST"})
     */
    public function diasLibresByOficina(TurnoRepository $turnoRepository, $oficina_id) {
        $turnosLibres = $turnoRepository->findDiasDisponiblesByOficina($oficina_id);
        return new JsonResponse($turnosLibres);
    }

    /**
     * @Route("/TurnosWeb/diasOcupadosOficina/{oficina_id}", name="diasOcupadosOficina", requirements = {"oficina_id" = "\d+"}, methods={"POST"})
     */
    public function diasOcupadosByOficina(TurnoRepository $turnoRepository, $oficina_id) {
        $diasOcupados = $turnoRepository->findDiasOcupadosByOficina($oficina_id);
        return new JsonResponse($diasOcupados);
    }

    /**
     * @Route("/TurnosWeb/horariosDisponiblesOficinaFecha/{oficina_id}/{fecha}", name="horarisDisponibles", methods={"POST"})
     */
    public function horariosDisponiblesByOficinaByFecha(TurnoRepository $turnoRepository, $oficina_id, $fecha) {
        $horariosDisponibles = $turnoRepository->findHorariosDisponiblesByOficinaByFecha($oficina_id, $fecha);
        return new JsonResponse($horariosDisponibles);
    }
}
