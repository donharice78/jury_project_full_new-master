<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\TokenGenerator;
use App\Form\RegistrationFormType;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException; // Add this line
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; // Add this line


class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager, 
        MailerInterface $mailer,
        TokenGenerator $tokenGenerator // Change this line
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $photo = $form->get('photo')->getData();

            if ($photo) {
                $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = uniqid() . '-' . $originalFilename . '.' . $photo->guessExtension();

                try {
                    $photo->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads',
                        $newFilename
                    );
                    $user->setPhoto('/uploads/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                    return $this->render('registration/register.html.twig', [
                        'registrationForm' => $form,
                    ]);
                }
            }

            // Encode le mot de passe
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            // Generate a verification token and set it to the user
            $verificationToken = $tokenGenerator->generateToken();
            $user->setVerificationToken($verificationToken);
            $user->setIsVerified(false); // Set user as not verified initially

            $entityManager->persist($user);
            $entityManager->flush();

            // Send verification email
            $mail = (new TemplatedEmail())
                ->from(new Address('contact@webdev77.fr', 'Enregistrement Utilisateur')) // IONOS email in the 'from' field
                ->to($user->getEmail()) // Send to the user's email
                ->subject('Veuillez confirmer votre compte')
                ->htmlTemplate('emails/verification.html.twig') // Change this to your verification email template
                ->context([
                    'data' => $user,
                    'verificationToken' => $verificationToken, // Pass the token to the email context
                ]);

            $mailer->send($mail);

            $this->addFlash('success', 'Inscription réussie ! Un email de vérification a été envoyé à votre adresse.');

            return $this->redirectToRoute('verifiy_reg_email');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    
    

    #[Route('/verifiy_reg_email', name: 'verifiy_reg_email')]
    public function verifiyRegEmail(): Response
    {
        // Affiche la page d'accueil
        return $this->render('verification/reg_verify.html.twig');
    }
}
