<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\ContactMessage;
use App\Form\ContactMessageType;
use App\Form\StudentMessageType;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/view')]
class ShowController extends AbstractController
{
    #[Route('/{username}', name: 'app_admin_user_show')]
    public function show(Request $request, User $user, EntityManagerInterface $entityManager, MailerInterface $mailer)
    {
        $user = $this->getUser();
        // Create the contact message form
        $contactMessage = new ContactMessage();
        $form = $this->createForm(StudentMessageType::class, $contactMessage);
        
        // Handle form submission
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Process the form data, e.g., send an email or save to database
            $entityManager->persist($contactMessage); // Save the ContactMessage entity
            $entityManager->flush(); // Save to database

            // Send the email
            $mail = (new TemplatedEmail())
                ->from(new Address('contact@webdev77.fr', 'Contact des étudiants')) // IONOS email in the 'from' field
                ->to('kolonelaboki78@gmail.com')
                ->subject('Soumission du formulaire de contact des étudiants')
                ->htmlTemplate('emails/studentcontact.html.twig')
                ->context(['data' => $contactMessage]);
        
            $mailer->send($mail);

            // Add flash message
            $this->addFlash('success', 'Votre demande de changement de cours a été soumise.');

            $contactMessage = new ContactMessage(); // Create a new instance to clear the form
            $form = $this->createForm(ContactMessageType::class, $contactMessage);

            // No redirection, stay on the same page
        }

        return $this->render('admin_user/student_dashboard.html.twig', [
            'user' => $user,
            'form' => $form->createView(), // Pass the form view to the template
        ]);
    }
}
