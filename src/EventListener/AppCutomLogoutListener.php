<?php

namespace App\EventListener;

use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Clase encargada del logout
 */
class AppCutomLogoutListener {

    private $_session;

    public function __construct(SessionInterface $session)
    {
        $this->_session = $session;
    }
    
    /**
     * Lógica luego del logout
     */
    public function onLogout(LogoutEvent $event)
    {
        // Remuevo la variable de session de la versión del software
        $this->_session->remove('realeaseGit');  
    }
}