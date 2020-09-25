<?php

namespace App\Security;

use App\Entity\Usuario;
use App\Repository\UsuarioRepository;
use App\Service\UtilService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Clase encargada de la autenticación de usuario
 */
class AppCustomAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    private $_urlGenerator;
    private $_csrfTokenManager;
    private $_passwordEncoder;
    private $_usuarioRepository;
    private $_utilService;
    private $_session;

    public function __construct(UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager,
        UserPasswordEncoderInterface $passwordEncoder, UsuarioRepository $usuarioRepository, SessionInterface $session) {
        $this->_urlGenerator = $urlGenerator;
        $this->_csrfTokenManager = $csrfTokenManager;
        $this->_passwordEncoder = $passwordEncoder;
        $this->_usuarioRepository = $usuarioRepository;
        $this->_session = $session;
    }

    public function supports(Request $request)
    {
        return 'app_login' === $request->attributes->get('_route')
        && $request->isMethod('POST');
    }

    /**
     * Obtiene las credenciales
     */
    public function getCredentials(Request $request)
    {
        $credentials = [
            'username' => $request->request->get('username'),
            'password' => $request->request->get('password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    /**
     * Obtiene el usuario
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->_csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $user = $this->_usuarioRepository->findOneBy(['username' => $credentials['username']]);

        // Si no existe el usuario, muestro un cartel avisando la situación
        if (!$user) {
            throw new CustomUserMessageAuthenticationException('No se pudo encontrar el nombre de usuario.');
        }

        // Si el usuario esta dado de baja, muestro un cartel avisando la situación
        if ($user->getFechaBaja() != null) {
            throw new CustomUserMessageAuthenticationException('El usuario ingresado ha sido dado de baja. Comuníquese con la Secretaría de Informática.');
        }

        return $user;
    }

    /**
     * Chequea la credenciales
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->_passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    /**
     * Lógica luego del logueo
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // Una vez logueado correctamente, guardo dentro del usuario logueado la fecha de último acceso y sumarizo el contador de entradas
        $user = $this->_usuarioRepository->findOneBy(['username' => $request->request->get('username')]);
        if ($user) {
            $this->_usuarioRepository->actualizarLogueoUsuario($user);
        }

        // Seteo la variable globlal en session de realease en en el twig
        $utilService = new UtilService();
        $this->_session->set('realeaseGit', $utilService->getRelease());
        
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        // Redirigo el flujo hacia el list de turnos
        return new RedirectResponse($this->_urlGenerator->generate('turno_index'));
    }

    protected function getLoginUrl()
    {
        return $this->_urlGenerator->generate('app_login');
    }
}
