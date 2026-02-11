<?php

namespace App\Controller;

use App\Entity\Reviews;
use App\Entity\Orders;
use App\Entity\User;
use App\Enum\Status;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;


#[Route('/reviews', name: 'app_reviews_')]
final class ReviewsController extends AbstractController
{
    #[Route('', name: 'add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    #[OA\POST(
        summary: 'Ajouter un avis',
        description: 'Permet à un utilisateur de laisser un avis sur une commande terminée.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                example: [
                    'orderId' => 123,
                    'rating' => 4,
                    'comment' => 'Produit de bonne qualité, livraison rapide.'
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Avis créé avec succès',
                content: new OA\JsonContent(
                    example: [
                        'id' => 1,
                        'rating' => 4,
                        'comment' => 'Produit de bonne qualité, livraison rapide.'
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Requête invalide',
                content: new OA\JsonContent(
                    example: ['message' => 'Commande non terminée']
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Accès interdit',
                content: new OA\JsonContent(
                    example: ['message' => 'Accès interdit']
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Commande introuvable',
                content: new OA\JsonContent(
                    example: ['message' => 'Commande introuvable']
                )
            )
        ]
    )]
    public function add(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $order = $entityManager->getRepository(Orders::class)->find($data['orderId']);

        if (!$order) {
            return new JsonResponse(['message' => 'Commande introuvable'], 404);
        }

        // vérifier que la commande appartient au user
        if ($order->getUser() !== $user) {
            return new JsonResponse(['message' => 'Accès interdit'], 403);
        }

        // vérifier statut enum
        if ($order->getStatus() !== Status::en_attente_de_retour) {
            return new JsonResponse(
                ['message' => 'Commande non terminée'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $existingReview = $entityManager
            ->getRepository(Reviews::class)
            ->findOneBy(['commande' => $order]);

        if ($existingReview) {
            return new JsonResponse(['message' => 'Avis déjà existant'], 400);
        }

        $review = new Reviews();
        $review->setRating($data['rating']);
        $review->setComment($data['comment']);
        $review->setCommande($order);
        $review->setIsValidated(false);
        $review->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($review);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $review->getId(),
            'rating' => $review->getRating(),
            'comment' => $review->getComment(),
        ], Response::HTTP_CREATED);
    }

    #[Route('', name: 'show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        summary: 'Afficher les avis validés',
        description: 'Permet à un administrateur de voir tous les avis validés.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des avis validés',
                content: new OA\JsonContent(
                    example: [
                        [
                            'id' => 1,
                            'rating' => 4,
                            'comment' => 'Produit de bonne qualité, livraison rapide.',
                            'createdAt' => '2024-06-01 12:34:56'
                        ],
                        [
                            'id' => 2,
                            'rating' => 5,
                            'comment' => 'Excellent service, je recommande !',
                            'createdAt' => '2024-06-02 14:20:00'
                        ]
                    ]
                )
            )
        ]
    )]
    public function show(EntityManagerInterface $entityManager): JsonResponse
    {
        $reviews = $entityManager
            ->getRepository(Reviews::class)
            ->findBy(['isValidated' => true], ['createdAt' => 'DESC']);
        $responseData = [];

        foreach ($reviews as $review) {
            $responseData[] = [
                'id' => $review->getId(),
                'rating' => $review->getRating(),
                'comment' => $review->getComment(),
                'createdAt' => $review->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }
        return new JsonResponse($responseData, Response::HTTP_OK);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        summary: 'Supprimer un avis',
        description: 'Permet à un administrateur de supprimer un avis.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID de l\'avis à supprimer',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Avis supprimé avec succès',
                content: new OA\JsonContent(
                    example: ['message' => 'Avis supprimé avec succès']
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Avis introuvable',
                content: new OA\JsonContent(
                    example: ['message' => 'Avis introuvable']
                )
            )
        ]
    )]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $review = $entityManager->getRepository(Reviews::class)->find($id);

        if (!$review) {
            return new JsonResponse(['message' => 'Avis introuvable'], 404);
        }

        $entityManager->remove($review);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Avis supprimé avec succès'], Response::HTTP_OK);
    }

    #[Route('/{id}/validate', name: 'validate', methods: ['PATCH'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    #[OA\Patch(
        summary: 'Valider un avis',
        description: 'Permet à un employé de valider un avis en changeant son statut.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID de l\'avis à valider',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Avis validé avec succès',
                content: new OA\JsonContent(
                    example: ['message' => 'Avis validé avec succès']
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Avis déjà validé ou autre erreur',
                content: new OA\JsonContent(
                    example: ['message' => 'Avis déjà validé']
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Avis introuvable',
                content: new OA\JsonContent(
                    example: ['message' => 'Avis introuvable']
                )
            )
        ]
    )]
    public function validateReview(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $review = $entityManager
            ->getRepository(Reviews::class)
            ->find($id);

        if (!$review) {
            return new JsonResponse(
                ['message' => 'Avis introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        if ($review->isValidated()) {
            return new JsonResponse(
                ['message' => 'Avis déjà validé'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $review->setIsValidated(true);
        $entityManager->flush();

        return new JsonResponse(
            ['message' => 'Avis validé avec succès'],
            Response::HTTP_OK
        );
    }
}
