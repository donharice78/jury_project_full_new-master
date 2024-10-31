<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class VerificationController extends AbstractController
{
    #[Route('/verify/{token}', name: 'app_verify')]
    public function verify(string $token, EntityManagerInterface $entityManager): Response
    {
        // Find the user by the verification token
        $user = $entityManager->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            // Token not found, show an error message
            $this->addFlash('error', 'Token de vérification invalide.');
            return $this->redirectToRoute('app_login');
        }

        // Set the user as verified
        $user->setIsVerified(true);
        $user->setVerificationToken(null); // Clear the verification token
        $entityManager->flush(); // Persist changes

        $this->addFlash('success', 'Votre compte a été vérifié avec succès. Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/verify-reminder', name: 'app_verify_reminder')]
    public function reminder(): Response
    {
        // This can be a simple message or a redirect to the verification page
        return $this->render('verification/reminder.html.twig', [
            'message' => 'Veuillez vérifier votre email pour activer votre compte.',
        ]);
    }
}
