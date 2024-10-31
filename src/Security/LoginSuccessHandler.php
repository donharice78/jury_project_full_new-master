<?php
namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        // Get the logged-in user
        $user = $token->getUser();

        // Check if the user is verified
        if ($user instanceof User && !$user->isVerified()) {
            // Add a flash message to inform the user
            $request->getSession()->getFlashBag()->add('warning', 'Veuillez vérifier votre adresse e-mail pour activer votre compte.');

            // Redirect to a verification reminder page or stay on the same page
            return new RedirectResponse($this->router->generate('app_verify_reminder')); // Adjust route as needed
        }

        // Get the user's roles
        $roles = $user->getRoles();

        // Redirect based on user role
        if (in_array('ROLE_ADMIN', $roles)) {
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        } elseif (in_array('ROLE_USER', $roles)) {
            return new RedirectResponse($this->router->generate('app_admin_user_show', ['username' => $user->getUsername()]));
        }

        // Default redirection if no roles matched
        return new RedirectResponse($this->router->generate('homepage'));
    }
}
