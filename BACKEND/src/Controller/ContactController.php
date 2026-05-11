<?php

namespace App\Controller;

use App\Service\MailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    public function __construct(
        private MailService $mailService
    ) {}

    #[Route('/contact', name: 'app_contact', methods: ['POST'])]
    public function contact(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $name = $data['firstName'] ?? 'Anonyme';
        $email = $data['email'] ?? '';
        $message = $data['message'] ?? '';

        if ($email) {
            $this->mailService->sendContactConfirmation($name, $email, $message);
        }
        return new Response('Message reçu, merci de nous avoir contactés !', Response::HTTP_OK);
    }
    //afficher dans la page admin
    #[Route('/contact/messages', name: 'app_contact_messages', methods: ['GET'])]
    public function getMessages(): Response
    {        // Ici, tu devrais récupérer les messages depuis la base de données
        // Par exemple : $messages = $this->getDoctrine()->getRepository(ContactMessage::class)->findAll();
        $messages = []; // Remplace par la vraie récupération des messages

        return $this->json($messages);
    }
}
