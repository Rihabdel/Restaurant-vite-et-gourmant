<?php

namespace App\Controller;

use App\Entity\Dishes;
use App\Entity\Allergens;
use App\Entity\DishAllergen;
use App\Repository\DishAllergenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use dateTimeImmutable;
use Symfony\Component\Serializer\Attribute\Groups;

#[Route('api/dish_allergen', name: 'app_api_dish_allergens_')]
final class DishAllergenController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private SerializerInterface $serializer
    ) {}
    // ajouter un allergène à un plat
    #[Route('/{id}', name: 'add_allergen', methods: ['POST'])]
    #[OA\Post(
        summary: 'Ajouter un allergène à un plat',
        description: 'Cette endpoint permet d\'associer un allergène à un plat en fournissant l\'ID du plat et l\'ID de l\'allergène.',
        tags: ['DishAllergen'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID du plat',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            description: 'Données pour associer un allergène à un plat',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'allergen_id',
                        type: 'integer',
                        description: 'ID de l\'allergène à associer au plat'
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Allergène ajouté au plat avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'dish_id', type: 'integer'),
                        new OA\Property(property: 'dish_name', type: 'string'),
                        new OA\Property(property: 'allergen_id', type: 'integer'),
                        new OA\Property(property: 'allergen_name', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Requête invalide (ex. JSON mal formé, champ manquant)'
            ),
            new OA\Response(
                response: 404,
                description: 'Plat ou allergène non trouvé'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation échouée (ex. données invalides)'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    public function addAllergenToDish(Request $request, int $id): JsonResponse
    {
        try {
            //  chercher le plat
            $dish = $this->entityManager->getRepository(Dishes::class)->find($id);
            if (!$dish) {
                return new JsonResponse(
                    ['error' => 'Plat non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }
            // 1. Récupérer les données JSON de la requête
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(
                    ['error' => 'JSON invalide: ' . json_last_error_msg()],
                    Response::HTTP_BAD_REQUEST
                );
            }
            //  Vérifier l'ID de l'allergène
            if (!isset($data['allergen_id'])) {
                return new JsonResponse(
                    ['error' => 'Le champ "allergen_id" est obligatoire'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $allergenId = $data['allergen_id'];
            // Trouver l'allergène
            $allergen = $this->entityManager->getRepository(Allergens::class)->find($allergenId);
            if (!$allergen) {
                return new JsonResponse(
                    ['error' => "Allergène avec ID $allergenId non trouvé"],
                    Response::HTTP_NOT_FOUND
                );
            }
            //  verifier si l'allergene est déjà associé au plat
            $existingRelation = $this->entityManager->getRepository(DishAllergen::class)
                ->findOneBy([
                    'dish' => $dish,
                    'allergen' => $allergen
                ]);

            if (!$existingRelation) {

                $dishAllergen = new DishAllergen();
                $dishAllergen->setDish($dish);
                $dishAllergen->setAllergen($allergen);
            } else {
                return new JsonResponse(
                    ['message' => 'Le plat est déjà associé à cet allergène'],
                    Response::HTTP_OK
                );
            }


            // 7. Valider l'entité
            $errors = $this->validator->validate($dishAllergen);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }

                return new JsonResponse(
                    ['errors' => $errorMessages],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            // 8. Persister et sauvegarder
            $this->entityManager->persist($dishAllergen);
            $this->entityManager->flush();

            // 9. Retourner une réponse de succès
            return new JsonResponse(
                [
                    'message' => 'Allergène ajouté au plat avec succès',
                    'dish_id' => $dish->getId(),
                    'dish_name' => $dish->getName(),
                    'allergen_id' => $allergen->getId(),
                    'allergen_name' => $allergen->getName(),
                ],
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            // 10. Retourner une réponse d'erreur
            return new JsonResponse(
                ['error' => 'Erreur serveur: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // afficher la liste des allergènes associés à un plat
    #[Route('/{id}', methods: ['GET'], name: '_list')]
    #[OA\Get(
        summary: 'Récupérer la liste des allergènes associés à un plat',
        description: 'Cette endpoint permet de récupérer la liste des allergènes associés à un plat spécifique
        en fournissant l\'ID du plat.',
        tags: ['DishAllergen'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID du plat',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des allergènes récupérée avec succès',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'allergen_id', type: 'integer'),
                            new OA\Property(property: 'allergen_name', type: 'string'),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Plat non trouvé'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]

    )]
    public function getDishAllergenList(int $id): JsonResponse
    {
        $dish = $this->entityManager->getRepository(Dishes::class)->find($id);
        if (!$dish) {
            return new JsonResponse(
                ['message' => 'Plat non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
        $allergenList = $this->entityManager->getRepository(DishAllergen::class)->findAllergensByDishId($id);
        $responseData = $this->serializer->serialize(
            $allergenList,
            'json',
            ['groups' => ['dish_allergen:read', 'allergen:read']]
        );
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }
    #[Route('/{id}', methods: ['DELETE'], name: 'remove_allergen')]
    #[OA\Delete(
        summary: 'Supprimer un allergène d\'un plat',
        description: 'Cette endpoint permet de supprimer l\'association d\'un allergène à un plat en fournissant l\'ID du plat et l\'ID de l\'allergène.',
        tags: ['DishAllergen'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID du plat',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            description: 'Données pour supprimer l\'association d\'un allergène à un plat',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'allergen_id',
                        type: 'integer',
                        description: 'ID de l\'allergène à dissocier du plat'
                    )
                ]
            )
        )
    )]
    public function removeAllergenFromDish(DishAllergenRepository $dishAllergenRepository, Request $request, int $id): JsonResponse
    {

        $dish = $this->entityManager->getRepository(Dishes::class)->find($id);
        if (!$dish) {
            return new JsonResponse(
                ['message' => 'Plat non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
        $data = json_decode($request->getContent(), true);
        // Vérifier que l'ID de l'allergène est fourni
        if (!isset($data['allergen_id'])) {
            return new JsonResponse(
                ['message' => 'ID de l\'allergène manquant'],
                Response::HTTP_BAD_REQUEST
            );
        }
        $allergenId = $data['allergen_id'];
        $allergen = $this->entityManager->getRepository(Allergens::class)->find($allergenId);
        if (!$allergen) {
            return new JsonResponse(
                ['message' => 'Allergène non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
        $exists = $dishAllergenRepository->findOneBy([
            'dish' => $dish->getId(),
            'allergen' => $allergen->getId()
        ]);
        if (!$exists) {
            return new JsonResponse(
                ['message' => 'le plat n\'est pas associé à cet allergène'],
                Response::HTTP_NOT_FOUND
            );
        }

        $this->entityManager->remove($exists);
        $this->entityManager->flush();

        return new JsonResponse(
            ['message' => 'Allergène supprimé du plat avec succès'],
            Response::HTTP_OK
        );
    }
    // afficher le détail d'un allergène associé à un plat
    #[Route('/{id}/detail', methods: ['GET'], name: 'detail')]
    #[OA\Get(
        tags: ['DishAllergen'],
        summary: 'Récupérer le détail d\'un allergène associé à un plat',
        description: 'Cette endpoint permet de récupérer le détail d\'un allergène spécifique associé à un plat en fournissant l\'ID du plat et l\'ID de l\'allergène.',

        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID du plat',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détail de l\'allergène récupéré avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'allergen_id', type: 'integer'),
                        new OA\Property(property: 'allergen_name', type: 'string'),
                        new OA\Property(property: 'description', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Plat ou allergène non trouvé'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    public function detail(int $id): JsonResponse
    {
        $allergen = $this->entityManager->getRepository(Allergens::class)->find($id);
        if (!$allergen) {
            return new JsonResponse(
                ['message' => 'Allergène non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
        $allergenDetails = $this->entityManager->getRepository(DishAllergen::class)->findAllergensByDishId($id);
        $responseData = $this->serializer->serialize(
            $allergenDetails,
            'json',
            ['groups' => ['dish_allergen:read', 'allergen:read']]
        );
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }
}
