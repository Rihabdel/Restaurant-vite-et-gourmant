<?php

namespace App\Controller;


use App\Entity\Allergens;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AllergensRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use symfony\component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use OpenApi\Attributes as OA;

#[Route('/api/allergens', name: 'app_api_allergens_')]
final class AllergensController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,


    ) {}

    #[Route('/new', methods: ['POST'], name: 'new')]
    //#[IsGranted('ROLE_ADMIN')]
    #[OA\Post(
        tags: ['Allergens'],
        summary: 'Créer un nouvel allergène',
        description: 'Cette endpoint permet de créer un nouvel allergène.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Gluten'),
                    new OA\Property(property: 'description', type: 'string', example: 'Protéine présente dans le blé, l\'orge et le seigle.'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Allergène créé avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Gluten'),
                        new OA\Property(property: 'description', type: 'string', example: 'Protéine présente dans le blé, l\'orge et le seigle.'),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2024-06-01T12:00:00Z'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Requête invalide'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur interne du serveur'
            ),
        ]
    )]
    public function new(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des champs obligatoires
        if (empty($data['name']) || empty($data['description'])) {
            return new JsonResponse(
                ['error' => 'Le champ nom et de description de l\'allergène est obligatoire'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $allergen = new Allergens();
        $allergen->setName($data['name']);
        $allergen->setDescription($data['description']);
        $allergen->setCreatedAt(new DateTimeImmutable());

        $this->entityManager->persist($allergen);
        $this->entityManager->flush();

        $responseData = $this->serializer->serialize($allergen, 'json');
        $location = $this->generateUrl(
            'app_api_allergens_show',
            ['id' => $allergen->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return new JsonResponse($responseData, Response::HTTP_CREATED, ['Location' => $location], true);
    }


    #[Route('/{id}', methods: ['GET'], name: 'show')]
    #[OA\Get(
        tags: ['Allergens'],
        summary: 'Récupérer un allergène par ID',
        description: 'Cette endpoint permet de récupérer les détails d\'un allergène spécifique en utilisant son ID.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID de l\'allergène à récupérer',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)

            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Allergène récupéré avec succès'
            ),
            new OA\Response(
                response: 404,
                description: 'Allergène non trouvé'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur interne du serveur'
            ),
        ]
    )]
    public function show(int $id, EntityManagerInterface $entityManager): JsonResponse
    {

        $allergen = $entityManager->getRepository(Allergens::class)->find($id);
        if (!$allergen) {
            return new JsonResponse(
                ['message' => 'Allergène non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
        return new JsonResponse(
            [
                'id' => $allergen->getId(),
                'name' => $allergen->getName(),
                'description' => $allergen->getDescription(),
                'createdAt' => $allergen->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', methods: ['PUT'], name: 'edit')]
    //#[IsGranted('ROLE_ADMIN')]
    #[OA\Put(
        tags: ['Allergens'],
        summary: 'Mettre à jour un allergène',
        description: 'Cette endpoint permet de mettre à jour les détails d\'un allergène existant.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID de l\'allergène à mettre à jour',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Gluten'),
                    new OA\Property(property: 'description', type: 'string', example: 'Protéine présente dans le blé, l\'orge et le seigle.'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Allergène mis à jour avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Gluten'),
                        new OA\Property(property: 'description', type: 'string', example: 'Protéine présente dans le blé, l\'orge et le seigle.'),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2024-06-01T12:00:00Z'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Requête invalide'
            ),
            new OA\Response(
                response: 404,
                description: 'Allergène non trouvé'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur interne du serveur'
            ),
        ]
    )]
    public function edit(EntityManagerInterface $entityManager, int $id, Request $request): Response
    {
        try {
            $allergen = $entityManager->getRepository(Allergens::class)->find($id);
            if (!$allergen) {
                return new JsonResponse(
                    ['message' => 'Allergen not found'],
                    Response::HTTP_NOT_FOUND
                );
            }

            $data = json_decode($request->getContent(), true);

            if (!isset($data['name'])) {
                $allergen->setName($data['name']);
            }
            if (!isset($data['description'])) {
                $allergen->setDescription($data['description']);
            }
            $allergen = new Allergens();
            $allergen->setCreatedAt(new DateTimeImmutable());
            $allergen->setIcon($data['icon'] ?? null);
            $allergen->setName($data['name'] ?? null);
            $allergen->setDescription($data['description'] ?? null);
            $entityManager->persist($allergen);

            $entityManager->flush();

            $responseData = $this->serializer->serialize($allergen, 'json', [
                'groups' => ['allergen:read']
            ]);
            $location = $this->generateUrl(
                'app_api_allergens_show',
                ['id' => $allergen->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            return new JsonResponse($responseData, Response::HTTP_OK, ['Location' => $location], true);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la mise à jour de l\'allergène: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    #[Route('/{id}', methods: ['DELETE'], name: 'delete')]
    //#[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        tags: ['Allergens'],
        summary: 'Supprimer un allergène',
        description: 'Cette endpoint permet de supprimer un allergène existant.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID de l\'allergène à supprimer',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Allergène supprimé avec succès'
            ),
            new OA\Response(
                response: 404,
                description: 'Allergène non trouvé'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur interne du serveur'
            ),
        ]
    )]
    public function delete(EntityManagerInterface $entityManager, int $id): Response
    {
        try {
            $allergen = $entityManager->getRepository(Allergens::class)->find($id);
            if (!$allergen) {
                return new JsonResponse(
                    ['message' => 'Allergen not found'],
                    Response::HTTP_NOT_FOUND
                );
            }
            foreach ($allergen->getDishAllergens() as $dishAllergen) {
                $this->entityManager->remove($dishAllergen);
            }
            $entityManager->remove($allergen);
            $entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la suppression de l\'allergène: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
