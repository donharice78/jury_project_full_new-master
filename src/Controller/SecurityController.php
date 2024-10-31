<?php
namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Component\Mime\Address;
use App\Form\SetNewPasswordFormType;
use App\Form\ResetPasswordRequestType;
use App\Exception\TokenExpiredException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key in your firewall.');
    }

    #[Route(path: '/reset-password', name: 'app_reset_password_request')]
    public function resetPasswordRequest(Request $request, MailerInterface $mailer, UserRepository $userRepository, UrlGeneratorInterface $urlGenerator): Response
    {
        $form = $this->createForm(ResetPasswordRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data['email'];

            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                $resetToken = bin2hex(random_bytes(32));
                $user->setVerificationToken($resetToken);
                $user->setTokenExpiration(new \DateTimeImmutable('+1 hour'));

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $resetLink = $urlGenerator->generate('app_reset_password', ['token' => $resetToken], UrlGeneratorInterface::ABSOLUTE_URL);

                $emailMessage = (new TemplatedEmail())
                    ->from(new Address('contact@webdev77.fr', 'Web Dev Contact'))
                    ->to($email)
                    ->subject('Password Reset Request')
                    ->html("<p>Please click the link to reset your password: <a href=\"$resetLink\">Reset Password</a></p>");

                $mailer->send($emailMessage);
                $this->addFlash('success', 'We have sent you an email to reset your password.');
            } else {
                $this->addFlash('error', 'No account found with that email address.');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('password_reset/reset.html.twig', [
            'resetPasswordRequestForm' => $form->createView(),
        ]);
    }

    #[Route(path: '/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(string $token, Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $userRepository->findOneBy(['verificationToken' => $token]);

        if (!$user || !$user->isTokenValid()) {
            throw new TokenExpiredException('Ce lien n\'est plus valide.');
        }

        $form = $this->createForm(SetNewPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
            $user->setVerificationToken(null);
            $user->setTokenExpiration(null);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Your password has been reset successfully. You can now log in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('password_reset/reset_password.html.twig', [
            'resetPasswordForm' => $form->createView(),
        ]);
    }
}
