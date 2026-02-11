<?php

namespace App\Controller;

use App\Entity\Dishes;
use App\Entity\Allergens;
use App\Entity\DishAllergen;
use App\Enum\AllergenTypeEnum;
use App\Enum\CategoryDishes;
use App\Repository\DishesRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use symfony\component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('api/dishes', name: 'app_api_dishes_')]
final class DishesController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('/new', methods: ['POST'], name: 'new')]
    #[
        OA\Post(
            tags: ['Dish'],
            summary: 'Créer un nouveau plat',
            description: 'Cette endpoint permet de créer un nouveau plat dans le menu.',
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    example: [
                        'name' => 'Pizza Margherita',
                        'description' => 'Une pizza classique avec sauce tomate, mozzarella et basilic.',
                        'price' => 12.99,
                        'category' => 'main_course',
                        'allergens' => [1, 2] // IDs des allergènes associés
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: 'Plat créé avec succès',
                    content: new OA\JsonContent(
                        example: [
                            'id' => 1,
                            'name' => 'Pizza Margherita',
                            'description' => 'Une pizza classique avec sauce tomate, mozzarella et basilic.',
                            'price' => 12.99,
                            'category' => 'main_course',
                            'createdAt' => '2024-06-01T12:00:00Z',
                            'allergens' => [
                                ['id' => 1, 'name' => 'Gluten'],
                                ['id' => 2, 'name' => 'Lactose']
                            ]
                        ]
                    )
                ),
                new OA\Response(
                    response: 400,
                    description: 'Requête invalide'
                ),
                new OA\Response(
                    response: 422,
                    description: 'Validation échouée'
                ),
                new OA\Response(
                    response: 500,
                    description: 'Erreur serveur'
                )
            ]
        )

    ]
    public function new(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation des champs obligatoires
            if (!isset($data['name']) || !isset($data['description']) || !isset($data['price'])) {
                return new JsonResponse(
                    ['error' => 'Les champs nom du plat, description et prix sont obligatoires'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Validation de la catégorie
            if (!isset($data['category'])) {
                return new JsonResponse(
                    ['error' => 'La catégorie est obligatoire'],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $category = CategoryDishes::tryFrom($data['category']);
            if (!$category) {
                return new JsonResponse(
                    ['error' => 'La catégorie est invalide. Les valeurs possibles sont: ' .
                        implode(', ', array_map(fn($e) => $e->value, CategoryDishes::cases()))],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            $allergenIds = [];
            if (isset($data['allergens'])) {
                if (is_array($data['allergens'])) {

                    $allergenIds = $data['allergens'];
                } else {
                    return new JsonResponse(
                        ['error' => 'Le champ allergènes doit être un tableau d\'IDs'],
                        Response::HTTP_UNPROCESSABLE_ENTITY
                    );
                }
            }

            // Préparer les données pour la désérialisation
            unset($data['category']);
            if (isset($data['allergens'])) {
                unset($data['allergens']);
            }

            $jsonSansEnum = json_encode($data);
            $dish = $this->serializer->deserialize(
                $jsonSansEnum,
                Dishes::class,
                'json'
            );

            $dish->setCategory($category);
            $dish->setCreatedAt(new DateTimeImmutable());

            // Gestion des allergènes associés
            if (!empty($allergenIds)) {
                foreach ($allergenIds as $allergenId) {
                    $allergen = $this->entityManager->getRepository(Allergens::class)->find($allergenId);
                    if ($allergen) {
                        $dishAllergen = new DishAllergen();
                        $dishAllergen->setDish($dish);
                        $dishAllergen->setAllergen($allergen);
                        $this->entityManager->persist($dishAllergen);
                    }
                }
            }

            // Validation de l'entité
            $errors = $this->validator->validate($dish);
            if (count($errors) > 0) {
                $messagesErreur = [];
                foreach ($errors as $error) {
                    $messagesErreur[] = [
                        'champ' => $error->getPropertyPath(),
                        'message' => $error->getMessage()
                    ];
                }

                return new JsonResponse(
                    ['erreurs' => $messagesErreur],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            // Persist et flush
            $this->entityManager->persist($dish);
            $this->entityManager->flush();

            $responseData = $this->serializer->serialize($dish, 'json', [
                'groups' => ['dish:read']
            ]);

            $location = $this->generateUrl(
                'app_api_dishes_show',
                ['id' => $dish->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            return new JsonResponse($responseData, Response::HTTP_CREATED, [
                'Location' => $location
            ], true);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Erreur serveur: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    #[Route('/{id}', methods: ['GET'], name: 'show')]
    #[OA\Get(
        tags: ['Dish'],
        summary: 'Afficher les détails d\'un plat',
        description: 'Cette endpoint permet de récupérer les détails d\'un plat spécifique en utilisant son ID.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID du plat à récupérer',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détails du plat récupérés avec succès',
                content: new OA\JsonContent(
                    example: [
                        'id' => 1,
                        'name' => 'nom du plat',
                        'description' => 'description du plat.',
                        'price' => 12.99,
                        'category' => 'entree',
                        'createdAt' => '2024-06-01T12:00:00Z',
                        'allergens' => [
                            ['id' => 1, 'name' => 'nom de l\'allergène'],
                            ['id' => 2, 'name' => 'nom de l\'allergène']
                        ]
                    ]
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

    public function show(int $id, EntityManagerInterface $em): Response

    {
        $dish = $em->getRepository(Dishes::class)->find($id);
        if (!$dish) {
            return new JsonResponse(
                ['message' => 'Dish not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        return new JsonResponse(
            $this->serializer->serialize($dish, 'json', ['groups' => ['dish:detail']]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{id}', methods: ['PUT'], name: 'edit')]
    #[OA\Put(
        tags: ['Dish'],
        summary: 'Mettre à jour un plat',
        description: 'Cette endpoint permet de mettre à jour les informations d\'un plat existant en utilisant son ID.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID du plat à mettre à jour',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                example: [
                    'name' => 'plat à mettre à jour',
                    'description' => 'description à mettre à jour.',
                    'price' => 14.99,
                    'category' => 'ENTREE',
                    'allergens' => [1, 2] // IDs des allergènes associés
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Plat mis à jour avec succès',
                content: new OA\JsonContent(
                    example: [
                        'id' => 1,
                        'name' => 'nom du plat mis à jour',
                        'description' => 'description du plat mis à jour.',
                        'price' => 14.99,
                        'category' => 'ENTREE',
                        'createdAt' => '2024-06-01T12:00:00Z',
                        'allergens' => [
                            ['id' => 1, 'name' => 'nom de l\'allergène'],
                            ['id' => 2, 'name' => 'nom de l\'allergène']
                        ]
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Requête invalide'
            ),
            new OA\Response(
                response: 404,
                description: 'Plat non trouvé'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation échouée'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {

        $dish = $em->getRepository(Dishes::class)->find($id);

        if (!$dish) {
            return $this->json(['error' => 'Dish not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        // Mettre à jour l'entité
        if (isset($data['name'])) {
            $dish->setName($data['name']);
        }

        // Validation
        $errors = $validator->validate($dish);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'error' => 'Validation failed',
                'errors' => $errorMessages
            ], 400);
        }

        $em->flush();

        return $this->json($dish, 200, [], ['groups' => ['dish:read']]);
    }

    #[Route('/{id}', methods: ['DELETE'], name: 'delete')]
    #[
        OA\Delete(
            tags: ['Dish'],
            summary: 'Supprimer un plat',
            description: 'Cette endpoint permet de supprimer un plat existant en utilisant son ID.',
            parameters: [
                new OA\Parameter(
                    name: 'id',
                    in: 'path',
                    description: 'ID du plat à supprimer',
                    required: true,
                    schema: new OA\Schema(type: 'integer')
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: 'Plat supprimé avec succès'
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
        )
    ]
    public function delete(EntityManagerInterface $entityManager, int $id, Request $request): Response
    {

        $dish = $entityManager->getRepository(Dishes::class)->find($id);
        if (!$dish) {
            return new JsonResponse(
                ['message' => 'Dishes not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        $entityManager->remove($dish);
        $entityManager->flush();
        return new JsonResponse(
            ['message' => 'Dishes deleted successfully'],
            Response::HTTP_OK
        );
    }
}
