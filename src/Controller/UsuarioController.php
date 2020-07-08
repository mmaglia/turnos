<?php

namespace App\Controller;

use App\DataTables\UsuarioTableType;
use App\Entity\Usuario;
use App\Form\UsuarioType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Omines\DataTablesBundle\DataTableFactory;

/**
 * @Route("/usuario")
 */
class UsuarioController extends AbstractController
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
     * @Route("/", name="usuario_index")
     */
    public function index(Request $request): Response
    {
        $table = $this->datatableFactory->createFromType(UsuarioTableType::class, array())->handleRequest($request);
        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('usuario/index.html.twig', ['datatable' => $table]);
    }

    /**
     * @Route("/new", name="usuario_new", methods={"GET","POST"})
     * 
     * @IsGranted("ROLE_EDITOR")
     */
    function new(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $usuario = new Usuario();
        $form = $this->createForm(UsuarioType::class, $usuario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $usuario->setFechaAlta(new \DateTime());
            $usuario->setPassword(
                $passwordEncoder->encodePassword($usuario, $form->get("password")->getData())
            );
            $usuario->setRoles(array_values($form->get("roles")->getData()));
            $usuario->setDni($form->get("dni")->getData());
            $usuario->setApellido($form->get("apellido")->getData());
            $usuario->setNombre($form->get("nombre")->getData());
            $usuario->setEmail($form->get("email")->getData());
            $entityManager->persist($usuario);

            $entityManager->flush();
            $this->addFlash('info', 'Se ha creado el usuario: ' . $usuario->getUsername());
            return $this->redirectToRoute('usuario_index');
        }

        return $this->render('usuario/new.html.twig', [
            'usuario' => $usuario,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="usuario_show", methods={"GET"})
     */
    public function show(Usuario $usuario): Response
    {
        return $this->render('usuario/show.html.twig', [
            'usuario' => $usuario,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="usuario_edit", methods={"GET","POST"})
     * 
     * @IsGranted("ROLE_EDITOR")
     */
    public function edit(Request $request, Usuario $usuario, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $form = $this->createForm(UsuarioType::class, $usuario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $usuario->setPassword(
                $passwordEncoder->encodePassword($usuario, $form->get("password")->getData())
            );

            $usuario->setRoles(array_values($form->get("roles")->getData()));

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('usuario_index');
        }

        return $this->render('usuario/edit.html.twig', [
            'usuario' => $usuario,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="usuario_delete", methods={"DELETE"})
     * 
     * @IsGranted("ROLE_EDITOR")
     */
    public function delete(Request $request, Usuario $usuario): Response
    {
        if ($this->isCsrfTokenValid('delete' . $usuario->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();

            //No borro definitvamente el usuario sino que le seteo una fecha de baja (baja lógica)
            $usuario->setFechaBaja(new \DateTime('now'));
            $entityManager->flush();
        }

        return $this->redirectToRoute('usuario_index');
    }
}
