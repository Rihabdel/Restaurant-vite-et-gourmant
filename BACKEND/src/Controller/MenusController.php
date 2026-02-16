<?php

namespace App\Controller;


use App\Entity\Menus;
use App\Enum\Theme;
use App\Enum\Diet;
use App\Repository\MenusRepository;
use App\Repository\MenusDishesRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use symfony\component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/menu', name: 'app_api_menus_')]
final class MenusController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,

    ) {}
    #ISadmin
    //#[IsGranted('ROLE_ADMIN', 'ROLE_EMPLLOYE')]
    #[Route('/new', name: 'new', methods: ['POST'])]
    #[OA\Post(
        tags: ['Menu'],
        summary: 'Créer un nouveau menu',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Menu du jour'),
                    new OA\Property(property: 'descriptionMenu', type: 'string', example: 'Un menu délicieux pour aujourd\'hui'),
                    new OA\Property(property: 'price', type: 'string', format: 'float', example: 19.99),
                    new OA\Property(property: 'minPeople', type: 'integer', example: 10),
                    new OA\Property(property: 'orderBefore', type: 'integer', example: 24),
                    new OA\Property(property: 'conditions', type: 'string', example: 'Aucune annulation possible après 24h avant la date de livraison'),
                    new OA\Property(property: 'stock', type: 'integer', example: 10),
                    new OA\Property(property: 'themeMenu', type: 'string', enum: ['classique', 'noel', 'anniversaire', 'mariage', 'paques'], example: 'classique'),
                    new OA\Property(property: 'dietMenu', type: 'string', enum: ['classique', 'vegetarien', 'vegan', 'sans_gluten', 'autres'], example: 'vegetarien'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Menu créé'),
            new OA\Response(response: 422, description: 'Erreur de validation')
        ]
    )]

    public function new(Request $request): JsonResponse
    {
        //decoder le json
        $data = json_decode($request->getContent(), true);
        if (!isset($data['themeMenu'])) {
            return new JsonResponse(
                ['erreurs' => 'Le thème est obligatoire'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        $themeMenu = Theme::tryFrom($data['themeMenu']);

        if (!$themeMenu) {
            return new JsonResponse(
                ['erreurs' => 'Thème invalide'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        if (!isset($data['dietMenu'])) {
            return new JsonResponse(
                ['erreurs' => 'Le régime est obligatoire'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        $dietMenu = Diet::tryFrom($data['dietMenu']);

        if (!$dietMenu) {
            return new JsonResponse(
                ['erreurs' => 'Régime invalide'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        //supprimer les champs enum du tableau
        unset($data['themeMenu']);
        unset($data['dietMenu']);
        //reecreer un json sans enum
        $jsonSansEnum = json_encode($data);
        //deserializer le json en entité
        $menu = $this->serializer->deserialize(
            $jsonSansEnum,
            Menus::class,
            'json'
        );
        //assigner les enums
        $menu->setThemeMenu($themeMenu);
        $menu->setDietMenu($dietMenu);
        //valider l'entité

        $errors = $this->validator->validate($menu);
        if (count($errors) > 0) {
            $messagesErreur = [];
            foreach ($errors as $error) {
                $messagesErreur[] = $error->getMessage();
            }

            return new JsonResponse(
                ['erreurs' => $messagesErreur],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        //persist et flush
        $menu->setCreatedAt(new DateTimeImmutable());
        $this->entityManager->persist($menu);
        $this->entityManager->flush();
        //serialize et return JsonResponse
        $responseData = $this->serializer->serialize($menu, 'json');
        $location = $this->generateUrl(
            'app_api_menus_show',
            ['id' => $menu->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return new JsonResponse($responseData, Response::HTTP_CREATED, [
            'Location' => $location
        ], true);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    #[OA\Get(
        tags: ['Menu'],
        summary: 'Lister les menus avec filtres optionnels',
        parameters: [
            new OA\Parameter(
                name: 'price_max',
                in: 'query',
                description: 'Prix maximum du menu',
                required: false,
                schema: new OA\Schema(type: 'number', format: 'float')
            ),
            new OA\Parameter(
                name: 'price_min',
                in: 'query',
                description: 'Prix minimum du menu',
                required: false,
                schema: new OA\Schema(type: 'number', format: 'float')
            ),
            new OA\Parameter(
                name: 'theme',
                in: 'query',
                description: 'Thème du menu (classique, noel, anniversaire, mariage, paques)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['classique', 'noel', 'anniversaire', 'mariage', 'paques'])
            ),
            new OA\Parameter(
                name: 'diet',
                in: 'query',
                description: 'Régime du menu (classique, vegetarien, vegan, sans_gluten, autres)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['classique', 'vegetarien', 'vegan', 'sans_gluten', 'autres'])
            ),
            new OA\Parameter(
                name: 'min_persons',
                in: 'query',
                description: 'Nombre minimum de personnes pour le menu',
                required: false,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des menus'),
            new OA\Response(response: 404, description: 'Aucun menu trouvé avec ces critères'),
            new OA\Response(response: 422, description: 'Erreur de validation des filtres')
        ]
    )]
    public function list(MenusRepository $repository, Request $request): JsonResponse
    {
        try {
            // Récupérer et normaliser les filtres
            $filters = [
                'price_max' => $request->query->get('price_max'),
                'price_min' => $request->query->get('price_min'),
                'theme' => $request->query->get('theme'),
                'diet' => $request->query->get('diet'),
                'min_persons' => $request->query->get('min_persons'),
            ];

            // Si pas de filtres, retourner tout
            if (count($filters) === 0) {
                $menu = $repository->findAll();
                error_log("=== METHODE findAll UTILISEE ===");
                error_log("Nombre de résultats: " . count($menu));
                $menusDishes = $this->entityManager->getRepository(MenusDishesRepository::class)->findAll();

                $responseData = $this->serializer->serialize($menusDishes, 'json', [
                    'groups' => ['menu:list', 'dish:read'],
                ]);
                return new JsonResponse($responseData, Response::HTTP_OK, [], true);
            }

            // Convertir les enums si présents
            if (isset($filters['theme'])) {
                $themeEnum = Theme::tryFrom($filters['theme']);
                if ($themeEnum === null) {
                    return new JsonResponse(
                        ['message' => 'Thème invalide. Valeurs acceptées: ' . implode(', ', array_column(Theme::cases(), 'value'))],
                        Response::HTTP_UNPROCESSABLE_ENTITY
                    );
                }
                $filters['theme'] = $themeEnum;
            }

            if (isset($filters['diet'])) {
                $dietEnum = Diet::tryFrom($filters['diet']);
                if ($dietEnum === null) {
                    return new JsonResponse(
                        ['message' => 'Régime invalide. Valeurs acceptées: ' . implode(', ', array_column(Diet::cases(), 'value'))],
                        Response::HTTP_UNPROCESSABLE_ENTITY
                    );
                }
                $filters['diet'] = $dietEnum;
            }

            //Appeler la méthode de filtrage du repository

            $menu = $repository->findWithFilters($filters);

            if (empty($menu)) {
                return new JsonResponse(
                    ['message' => 'Aucun menu trouvé avec ces critères'],
                    Response::HTTP_NOT_FOUND
                );
            }

            // 6. Sérialiser la réponse
            $responseData = $this->serializer->serialize($menu, 'json', [
                'groups' => 'menu:list'
            ]);

            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Erreur lors de la récupération des menus', 'message' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }
    }
    #[Route('/{id}', methods: ['GET'], name: 'show')]
    #[OA\Get(
        tags: ['Menu'],
        summary: 'Afficher les détails d\'un menu',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID du menu à afficher',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détails du menu'),
            new OA\Response(response: 404, description: 'Menu non trouvé')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $menus = $this->entityManager->getRepository(Menus::class)->find($id);
        if (!$menus) {
            return new JsonResponse(
                ['message' => 'Aucun menu non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
        $responseData = $this->serializer->serialize($menus, 'json', ['groups' => 'menu:read']);
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }
    //#[IsGranted('ROLE_ADMIN', 'ROLE_EMPLLOYE')]
    #[Route('/{id}', methods: ['PUT'], name: 'edit')]
    #[OA\Put(
        tags: ['Menu'],
        summary: 'Modifier un menu existant',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID du menu à modifier',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Menu du jour'),
                    new OA\Property(property: 'descriptionMenu', type: 'string', example: 'Un menu délicieux pour aujourd\'hui'),
                    new OA\Property(property: 'price', type: 'string', format: 'float', example: 19.99),
                    new OA\Property(property: 'orderBefore', type: 'integer', example: 24),
                    new OA\Property(property: 'minPeople', type: 'integer', example: 1),
                    new OA\Property(property: 'conditions', type: 'string', example: 4),
                    new OA\Property(property: 'stock', type: 'integer', example: 10),
                    new OA\Property(property: 'themeMenu', type: 'string', enum: ['classique', 'noel', 'anniversaire', 'mariage', 'paques'], example: 'classique'),
                    new OA\Property(property: 'dietMenu', type: 'string', enum: ['classique', 'vegetarien', 'vegan', 'sans_gluten', 'autres'], example: 'vegetarien'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Menu modifié'),
            new OA\Response(response: 404, description: 'Menu non trouvé'),
            new OA\Response(response: 422, description: 'Erreur de validation')
        ]
    )]
    public function edit(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $menus = $entityManager->getRepository(Menus::class)->find($id);
        if (!$menus) {
            return new JsonResponse(
                ['message' => 'Menus not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        $menus = $this->serializer->deserialize(
            file_get_contents('php://input'),
            Menus::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $menus]
        );

        $menus->setUpdatedAt(new DateTimeImmutable());
        $entityManager->persist($menus);
        $entityManager->flush();
        $responseData = $this->serializer->serialize($menus, 'json');
        $location = $this->generateUrl(
            'app_api_menus_show',
            ['id' => $menus->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return new JsonResponse($responseData, Response::HTTP_OK, [
            'Location' => $location
        ], true);
    }
    #[Route('/{id}', methods: ['DELETE'], name: 'delete')]
    #[OA\Delete(
        tags: ['Menu'],
        summary: 'Supprimer un menu',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID du menu à supprimer',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Menu supprimé'),
            new OA\Response(response: 404, description: 'Menu non trouvé')
        ]
    )]
    //#[IsGranted('ROLE_ADMIN', 'ROLE_EMPLLOYE')]
    public function delete(EntityManagerInterface $entityManager, int $id): Response
    {

        $menus = $entityManager->getRepository(Menus::class)->find($id);
        if (!$menus) {
            return new JsonResponse(
                ['message' => 'Menus not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        $entityManager->remove($menus);
        $entityManager->flush();
        return new JsonResponse(
            ['message' => 'Menus deleted successfully'],
            Response::HTTP_OK
        );
    }
}
