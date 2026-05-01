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
                        'price' => "12.99",
                        'category' => 'plat'
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
                            'category' => 'plat',
                            'createdAt' => '2024-06-01T12:00:00Z'
                        ]
                    )
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
            if (!$data) {
                return new JsonResponse(
                    ['error' => 'Données JSON invalides'],
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
            $dish = new Dishes();
            if (!empty($allergenIds)) {
                foreach ($allergenIds as $allergenId) {
                    $allergen = $this->entityManager
                        ->getRepository(Allergens::class)
                        ->find($allergenId);

                    if ($allergen) {
                        $dishAllergen = new DishAllergen();
                        $dishAllergen->setDish($dish);
                        $dishAllergen->setAllergen($allergen);
                        $dish->addDishAllergen($dishAllergen);
                        $this->entityManager->persist($dishAllergen);
                    }
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
                'groups' => ['dish:read', 'dish:write']
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

    #[Route('/list', methods: ['GET'], name: 'list')]
    #[
        OA\Get(
            tags: ['Dish'],
            summary: 'Lister tous les plats',
            description: 'Cette endpoint permet de récupérer la liste de tous les plats disponibles.',
            responses: [
                new OA\Response(
                    response: 200,
                    description: 'Liste des plats récupérée avec succès',
                    content: new OA\JsonContent(

                        example: [
                            "id" => 1,
                            "name" => "La Salade Landaise",
                            "description" => " Gésiers, magret fumé et/ou foie gras poêlé.",
                            "price" => "12.99",
                            "category" => "entree",
                            "allergenName" => [
                                "Arachides"
                            ]
                        ]
                    )
                ),
                new OA\Response(
                    response: 500,
                    description: 'Erreur serveur'
                )
            ]
        )
    ]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $dishes = $entityManager->getRepository(Dishes::class)->findAll();
        return $this->json($dishes, 200, [], [
            'groups' => ['dish:detail']
        ]);
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'], name: 'show')]
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
                        'name' => 'Pizza Margherita',
                        'description' => 'Une pizza classique avec sauce tomate, mozzarella et basilic.',
                        'price' => 12.99,
                        'category' => 'plat',
                        'createdAt' => '2024-06-01T12:00:00Z',
                        'allergens' => [
                            ['id' => 1, 'name' => 'Gluten'],
                            ['id' => 2, 'name' => 'Lactose']
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
    public function show($id, EntityManagerInterface $em): Response
    {
        $dish = $em->getRepository(Dishes::class)->find($id);
        if (!$dish) {
            return new JsonResponse(
                ['message' => 'Dish not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        return $this->json($dish, 200, [], ['groups' => ['dish:detail']]);
    }
    #[Route('/{id}', methods: ['PUT'], requirements: ['id' => '\d+'], name: 'update')]
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
                    'name' => 'Pizza Margherita',
                    'description' => 'Une pizza classique avec sauce tomate, mozzarella et basilic.',
                    'price' => "12.99",
                    'category' => 'plat'
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
                        'name' => 'Pizza Margherita',
                        'description' => 'Une pizza classique avec sauce tomate, mozzarella et basilic.',
                        'price' => 12.99,
                        'category' => 'plat',
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
            return new JsonResponse(
                ['message' => 'Dish not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(
                ['error' => 'Données JSON invalides'],
                Response::HTTP_BAD_REQUEST
            );
        }
        if (isset($data['category'])) {
            $category = CategoryDishes::tryFrom($data['category']);
            if (!$category) {
                return new JsonResponse(
                    ['error' => 'La catégorie est invalide. Les valeurs possibles sont: ' .
                        implode(', ', array_map(fn($e) => $e->value, CategoryDishes::cases()))],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            $dish->setCategory($category);
        }
        $jsonSansEnum = json_encode($data);
        $this->serializer->deserialize(
            $jsonSansEnum,
            Dishes::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $dish]
        );
        $em->flush();
        return $this->json($dish, 200, [], ['groups' => ['dish:detail']]);
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
