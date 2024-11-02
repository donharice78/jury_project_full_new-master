<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SessionExpirationListener
{
    private $router;
    private $logger;
    private $sessionExpirationTime;

    public function __construct(RouterInterface $router, LoggerInterface $logger, int $sessionExpirationTime = 600)
    {
        $this->router = $router;
        $this->logger = $logger;
        $this->sessionExpirationTime = $sessionExpirationTime;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        // Migrate the session to regenerate the session ID
        $session->migrate();

        // Check if the user is authenticated
        if ($session->has('_security_main')) { // Replace 'main' with your firewall name
            // Log the last used time and expiration check
            $lastUsed = $session->getMetadataBag()->getLastUsed();
            $this->logger->info('Session last used time.', ['last_used' => $lastUsed, 'current_time' => time()]);

            // Check if the session is still valid
            if ($lastUsed + $this->sessionExpirationTime < time()) {
                // The session has expired
                $this->logger->info('Session expired for user.', ['session_id' => $session->getId()]);
                $session->invalidate(); // Invalidate the session

                // Handle AJAX requests
                if ($request->isXmlHttpRequest()) {
                    $event->setResponse(new JsonResponse(['error' => 'Session expired.'], 401));
                    return;
                }

                // Redirect to login page
                $response = new RedirectResponse($this->router->generate('app_login'));
                $event->setResponse($response);
            }
        }
    }
}
