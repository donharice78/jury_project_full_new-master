<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use App\Form\ContactMessageType;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FormSubmissionController extends AbstractController
{
    #[Route('/form/submission', name: 'app_contact')]
    public function contact(Request $request, MailerInterface $mailer, EntityManagerInterface $entityManager): Response
    {
        $data = new ContactMessage();
        $form = $this->createForm(ContactMessageType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($data);
            $entityManager->flush();

            // Send the email
            $mail = (new TemplatedEmail())
            ->from(new Address('contact@webdev77.fr', 'Web Dev Contact')) // IONOS email in the 'from' field
            ->to('kolonelaboki78@gmail.com')
            ->subject('Contact Form Submission')
            ->htmlTemplate('emails/index.html.twig')
            ->context(['data' => $data]);
        
        $mailer->send($mail);

        $this->addFlash('success', 'Votre message a été envoyé et enregistré avec succès !');
        return $this->redirectToRoute('app_contact');
    }

        return $this->render('form_submission/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
