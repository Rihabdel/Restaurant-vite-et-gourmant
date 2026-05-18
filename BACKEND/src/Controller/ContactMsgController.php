<?php

namespace App\Controller;

use App\Entity\ContactMsg;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use OpenApi\Attributes as OA;

#[Route('/api/contact')]
class ContactMsgController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer

    ) {}

    #[Route('/all', name: 'contact_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        try {
            $contactMessages = $this->entityManager->getRepository(ContactMsg::class)->findAll();
            return $this->json($contactMessages, Response::HTTP_OK, [], ['groups' => 'contact:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la récupération des messages de contact'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/add', name: 'contact_add', methods: ['POST'])]
    #[OA\Post(
        summary: "Ajouter un message de contact",
        description: "Permet d'ajouter un nouveau message de contact",
        parameters: [
            new OA\RequestBody(
                description: "Données du message de contact",
                required: true,
                content: new OA\JsonContent(
                    example: [
                        'title' => 'Demande de réservation',
                        'email' => 'client@example.com',
                        'message' => 'Bonjour, je souhaiterais réserver pour deux personnes ce soir.'
                    ]
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: "Message de contact ajouté avec succès",
                content: new OA\JsonContent(
                    example: [
                        'message' => 'Message reçu avec succès'
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Requête invalide",
                content: new OA\JsonContent(
                    example: [
                        'error' => 'Tous les champs sont requis'
                    ]
                )
            )
        ]
    )]
    public function addContactMsg(Request $request, MailerInterface $mailer): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['title']) || empty($data['email']) || empty($data['message'])) {
            return $this->json([
                'error' => 'Tous les champs sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $contactMsg = new ContactMsg();
        $contactMsg->setTitle($data['title']);
        $contactMsg->setEmail($data['email'] ?? '');
        $contactMsg->setMessage($data['message'] ?? '');
        $contactMsg->setTraite(false);
        $contactMsg->setCreatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($contactMsg);
        $this->entityManager->flush();
        // Envoi de l'email de confirmation
        $mailService = new MailService($mailer);
        $mailService->sendContactConfirmation($data['title'], $data['email'], $data['message']);
        $mailService->sendContactEmail($data['title'], $data['email'], $data['message']);
        return $this->json([
            'message' => 'Message reçu avec succès'
        ], Response::HTTP_CREATED);
    }
    #[Route('/{id}', name: 'contact_delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: "Supprimer un message de contact",
        description: "Permet de supprimer un message de contact par son ID",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID du message de contact à supprimer",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Message de contact supprimé avec succès",
                content: new OA\JsonContent(
                    example: [
                        'message' => 'Message de contact supprimé avec succès'
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Message de contact non trouvé",
                content: new OA\JsonContent(
                    example: [
                        'error' => 'Message de contact non trouvé'
                    ]
                )
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteContactMsg(int $id): JsonResponse
    {
        try {
            $contactMsg = $this->entityManager->getRepository(ContactMsg::class)->find($id);
            if (!$contactMsg) {
                return $this->json([
                    'error' => 'Message de contact non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }
            $this->entityManager->remove($contactMsg);
            $this->entityManager->flush();
            $resposeData = $this->serializer->serialize($contactMsg, 'json', ['groups' => 'contact:read']);

            return $this->json($resposeData, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la suppression du message de contact'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route('/{id}/treated', name: 'contact_treated', methods: ['PUT'])]
    #[OA\Put(
        summary: "Marquer un message de contact comme traité",
        description: "Permet de marquer un message de contact comme traité par son ID",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID du message de contact à marquer comme traité",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Message de contact marqué comme traité avec succès",
                content: new OA\JsonContent(
                    example: [
                        'message' => 'Message de contact marqué comme traité avec succès'
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Message de contact non trouvé",
                content: new OA\JsonContent(
                    example: [
                        'error' => 'Message de contact non trouvé'
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erreur lors de la mise à jour du message de contact",
                content: new OA\JsonContent(
                    example: [
                        'error' => 'Une erreur est survenue lors de la mise à jour du message de contact'
                    ]
                )
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function markAsTreated(int $id): Response
    {
        try {
            $contactMsg = $this->entityManager->getRepository(ContactMsg::class)->find($id);
            if (!$contactMsg) {
                return $this->json([
                    'error' => 'Message de contact non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }
            if ($contactMsg->isTraite()) {
                return $this->json([
                    'message' => 'Ce message de contact est déjà marqué comme traité'
                ], Response::HTTP_OK);
            }
            $contactMsg->setTraite(true);
            $this->entityManager->flush();
            return $this->json([
                'message' => 'Message de contact marqué comme traité avec succès'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la mise à jour du message de contact'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
