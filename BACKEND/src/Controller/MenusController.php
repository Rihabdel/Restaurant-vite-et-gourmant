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
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


#[Route('/api/menu', name: 'app_api_menus_')]
final class MenusController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,

    ) {}

    #[IsGranted('ROLE_ADMIN')]
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
                    new OA\Property(property: 'stock', type: 'integer', example: 10),
                    new OA\Property(property: 'themeMenu', type: 'string', enum: ['classique', 'noel', 'anniversaire', 'mariage', 'paques'], example: 'classique'),
                    new OA\Property(property: 'dietMenu', type: 'string', enum: ['classique', 'vegetarien', 'vegan', 'sans_gluten', 'autres'], example: 'vegetarien'),
                    new OA\Property(property: 'available', type: 'boolean', example: true)
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
        //
        if (isset($data['themeMenu']) || isset($data['dietMenu'])) {
            $themeMenu = Theme::tryFrom($data['themeMenu']  ?? '');
            $dietMenu = Diet::tryFrom($data['dietMenu'] ?? '');
            if (!$themeMenu || !$dietMenu) {
                return $this->json([
                    'erreurs' => 'Thème ou Régime invalide/manquant',
                    'recu' => [
                        'theme' => $data['themeMenu'] ?? null,
                        'diet' => $data['dietMenu'] ?? null
                    ]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } else {
            return new JsonResponse(
                ['erreurs' => 'Thème et régime sont requis'],
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
        if (!isset($data['isAvailable'])) {
            $menu->setIsAvailable(0);
        } else {
            $menu->setIsAvailable($data['isAvailable']);
        }
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
        return  $this->json($menu, Response::HTTP_CREATED, [
            'Location' => $this->generateUrl(
                'app_api_menus_show',
                ['id' => $menu->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        ], ['groups' => 'menu:list']);
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
                'isAvailable' => $request->query->get('isAvailable')
            ];

            // Si pas de filtres, retourner tout
            if (count($filters) === 0) {
                $menu = $repository->findAll();
                error_log("=== METHODE findAll UTILISEE ===");
                error_log("Nombre de résultats: " . count($menu));
                $menusDishes = $this->entityManager->getRepository(MenusDishesRepository::class)->findAll();

                $responseData = $this->serializer->serialize($menusDishes, 'json', [
                    'groups' => ['menu:list'],
                ]);
                return new JsonResponse($responseData, Response::HTTP_OK, [], true);
            }
            // Convertir les enums 
            if (isset($filters['theme'])) {
                $filters['theme'] = Theme::tryFrom($filters['theme']);
            }

            if (isset($filters['diet'])) {
                $filters['diet'] = Diet::tryFrom($filters['diet']);
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
            return $this->json($menu, Response::HTTP_OK, [], [
                'groups' => ['menu:list', 'dish:list']
            ]);
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

        return $this->json(
            $menus,
            Response::HTTP_OK,
            [
                'Location' => $this->generateUrl(
                    'app_api_menus_show',
                    ['id' => $menus->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'groups' => ['menu:detail', 'dish:read', 'menu_dish:read']
            ]
        );
    }

    #[IsGranted('ROLE_ADMIN')]
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
                    new OA\Property(property: 'available', type: 'boolean', example: true),
                    new OA\Property(property: 'picture', type: 'string', format: 'base64', example: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA...')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Menu modifié'),
            new OA\Response(response: 404, description: 'Menu non trouvé'),
            new OA\Response(response: 422, description: 'Erreur de validation')
        ]
    )]
    public function edit(EntityManagerInterface $entityManager, int $id, Request $request): JsonResponse
    {
        $menus = $entityManager->getRepository(Menus::class)->find($id);
        if (!$menus) {
            return new JsonResponse(
                ['message' => 'Menus not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        //decoder le json
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(
                ['message' => 'Invalid JSON'],
                Response::HTTP_BAD_REQUEST
            );
        }
        $this->serializer->deserialize(
            json_encode($data),
            Menus::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $menus]
        );
        if (isset($data['themeMenu'])) {
            $themeMenu = Theme::tryFrom($data['themeMenu']);
            if (!$themeMenu) {
                return new JsonResponse(
                    ['erreurs' => 'Thème invalide'],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            $menus->setThemeMenu($themeMenu);
        }
        if (isset($data['dietMenu'])) {
            $dietMenu = Diet::tryFrom($data['dietMenu']);
            if (!$dietMenu) {
                return new JsonResponse(
                    ['erreurs' => 'Régime invalide'],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            $menus->setDietMenu($dietMenu);
        }

        $menus->setUpdatedAt(new DateTimeImmutable());
        $entityManager->persist($menus);
        $entityManager->flush();
        return $this->json($menus, Response::HTTP_OK, [
            'Location' => $this->generateUrl(
                'app_api_menus_show',
                ['id' => $menus->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        ], ['groups' => 'menu:list']);
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
    #[IsGranted("ROLE_ADMIN")]
    public function delete(EntityManagerInterface $entityManager, int $id): Response
    {

        $menus = $entityManager->getRepository(Menus::class)->find($id);
        if (!$menus) {
            return new JsonResponse(
                ['message' => 'Menus not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        try {
            $entityManager->remove($menus);
            $entityManager->flush();
            return new JsonResponse(
                ['message' => 'Menu supprimé avec succès'],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['message' => 'Erreur lors de la suppression du menu', 'error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    #[Route('/{id}/picture', name: 'picture', methods: ['POST'])]
    #[
        OA\Post(
            tags: ['Menu'],
            summary: 'Uploader une image pour un menu',
            parameters: [
                new OA\Parameter(
                    name: 'id',
                    in: 'path',
                    description: 'ID du menu',
                    required: true,
                    schema: new OA\Schema(type: 'integer')
                )
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: 'picture',
                                type: 'string',
                                format: 'binary',
                                description: 'Fichier image à uploader'
                            )
                        ]
                    )
                )
            ),
            responses: [
                new OA\Response(response: 200, description: 'Image uploadée avec succès'),
                new OA\Response(response: 400, description: 'Aucun fichier reçu ou erreur de validation'),
                new OA\Response(response: 404, description: 'Menu introuvable'),
                new OA\Response(response: 500, description: 'Erreur lors de l\'enregistrement de l\'image')
            ]
        )
    ]
    #[IsGranted("ROLE_ADMIN")]
    public function uploadPicture(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $menu = $em->getRepository(Menus::class)->find($id);

        if (!$menu) {
            return new JsonResponse(['error' => 'Menu introuvable'], 404);
        }

        $file = $request->files->get('picture');

        if (!$file) {
            return new JsonResponse(['error' => 'Aucun fichier reçu'], 400);
        }

        if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'])) {
            return new JsonResponse(['error' => 'Format invalide'], 400);
        }

        $extension = $file->guessExtension() ?: 'jpg';
        $fileName = uniqid() . '.' . $extension;

        try {
            $file->move(
                $this->getParameter('menus_pictures_directory'),
                $fileName
            );

            if ($menu->getPicture()) {
                $oldPath = rtrim($this->getParameter('menus_pictures_directory'), '/')
                    . '/' . $menu->getPicture();

                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $menu->setPicture($fileName);
            $em->flush();
        } catch (FileException $e) {
            return new JsonResponse(['error' => 'Erreur upload'], 500);
        }

        return new JsonResponse([
            'message' => 'Image enregistrée avec succès',
            'path' => $fileName
        ]);
    }
}
