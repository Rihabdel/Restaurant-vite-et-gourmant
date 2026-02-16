<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Entity\Menus;
use App\Entity\User;
use App\Enum\Status;
use App\Repository\OrdersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use DateTimeImmutable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('api/orders', name: 'app_api_orders_')]
final class OrdersController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('/new', methods: ['POST'], name: 'new')]
    #[IsGranted('ROLE_USER')]
    #[OA\Post(
        tags: ["Orders"],
        summary: "Créer une nouvelle commande",
        requestBody: new OA\RequestBody(
            description: "Détails de la commande",
            required: true,
            content: new OA\JsonContent(
                example: [
                    "menu" => 1,
                    "numberOfPeople" => 15,
                    "deliveryAddress" => "123 Rue Exemple",
                    "deliveryCity" => "Bordeaux",
                    "deliveryPostalCode" => "33000",
                    "deliveryDate" => "2026-03-31",
                    "deliveryTime" => "19:00"
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Commande créée avec succès",
                content: new OA\JsonContent(
                    example: [
                        "id" => 1,
                        "menu" => "Menu Dégustation",
                        "number_of_people" => 15,
                        "delivery_info" => [
                            "address" => "123 Rue Exemple",
                            "city" => "Bordeaux",
                            "postal_code" => "33000",
                            "date" => "2026-03-31",
                            "time" => "19:00"
                        ],
                        "prices" => [
                            "menu_price" => 100.0,
                            "delivery_cost" => 0.0,
                            "total_price" => 100.0
                        ],
                        "status" => "en_attente",
                        "created_at" => "2024-11-01 12:00:00"
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Requête invalide (ex: champ manquant, données invalides)"
            ),
            new OA\Response(
                response: 500,
                description: "Erreur serveur lors de la création de la commande"
            )
        ]
    )]
    public function new(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();


            $data = json_decode($request->getContent(), true);

            $requiredFields = [
                "menu",
                'numberOfPeople',
                'deliveryAddress',
                'deliveryCity',
                'deliveryPostalCode',
                'deliveryDate',
                'deliveryTime'
            ];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    return $this->json(['error' => "Champ manquant : $field"], Response::HTTP_BAD_REQUEST);
                }
            }

            $menu = $this->entityManager->getRepository(Menus::class)->find($data['menu']);
            if (!$menu) {
                return $this->json(['error' => 'Menu non trouvé'], Response::HTTP_BAD_REQUEST);
            }

            // Vérification du nombre de personnes
            if ($data['numberOfPeople'] < $menu->getMinPeople()) {
                return $this->json(['error' => "Minimum {$menu->getMinPeople()} personnes requises"], Response::HTTP_BAD_REQUEST);
            }

            if ($data['numberOfPeople'] > $menu->getStock()) {
                return $this->json(['error' => "Stock insuffisant. Disponible pour : {$menu->getStock()} personnes"], Response::HTTP_BAD_REQUEST);
            }

            $deliveryDateTime = new DateTimeImmutable($data['deliveryDate'] . ' ' . $data['deliveryTime']);
            $currentDate = new DateTimeImmutable();

            if ($deliveryDateTime < $currentDate) {
                return $this->json(['error' => 'La date de livraison doit être supérieure à la date actuelle'], Response::HTTP_BAD_REQUEST);
            }

            if ($deliveryDateTime < $currentDate->modify('+' . $menu->getOrderBefore() . ' hours')) {
                return $this->json(['error' => "La date de livraison doit respecter le délai de préparation ({$menu->getOrderBefore()} heures)"], Response::HTTP_BAD_REQUEST);
            }

            $deliveryCost = $this->calculateDeliveryCost($data['deliveryCity'], $data['deliveryPostalCode']);

            $order = new Orders();
            $order->setMenu($menu)
                ->setUser($user)
                ->setNumberOfPeople($data['numberOfPeople'])
                ->setDeliveryAddress($data['deliveryAddress'])
                ->setDeliveryCity($data['deliveryCity'])
                ->setDeliveryPostalCode($data['deliveryPostalCode'])
                ->setDeliveryDate(new \DateTime($data['deliveryDate']))
                ->setDeliveryTime(new \DateTime($data['deliveryTime']))
                ->setCreatedAt(new \DateTimeImmutable())
                ->setDeliveryCost($deliveryCost)
                ->setTotalPrice($menu->calculate_total_price($data['numberOfPeople']) + $deliveryCost)
                ->setStatus(Status::en_attente->value);

            $errors = $this->validator->validate($order);
            if (count($errors) > 0) {
                $messages = array_map(fn($e) => $e->getMessage(), iterator_to_array($errors));
                return $this->json(['errors' => $messages], Response::HTTP_BAD_REQUEST);
            }

            // Mettre à jour le stock
            $menu->setStock($menu->getStock() - $data['numberOfPeople']);

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            $location = $this->generateUrl('app_api_orders_list', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            return $this->json([
                'id' => $order->getId(),
                'menu' => $menu->getTitle(),
                'number_of_people' => $order->getNumberOfPeople(),
                'delivery_info' => [
                    'address' => $order->getDeliveryAddress(),
                    'city' => $order->getDeliveryCity(),
                    'postal_code' => $order->getDeliveryPostalCode(),
                    'date' => $order->getDeliveryDate()->format('Y-m-d'),
                    'time' => $order->getDeliveryTime()->format('H:i')
                ],
                'prices' => [
                    'menu_price' => $menu->getPrice(),
                    'delivery_cost' => $deliveryCost,
                    'total_price' => $order->getTotalPrice()
                ],
                'status' => $order->getStatus(),
                'created_at' => $order->getCreatedAt()->format('Y-m-d H:i:s')
            ], Response::HTTP_CREATED, ['Location' => $location]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la création de la commande: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', methods: ['GET'], name: 'show')]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        tags: ["Orders"],
        summary: "Afficher les détails d'une commande",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la commande à afficher",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Détails de la commande récupérés avec succès",
                content: new OA\JsonContent(
                    example: [
                        "id" => 1,
                        "menu" => "Menu Dégustation",
                        "number_of_people" => 15,
                        "delivery_info" => [
                            "address" => "123 Rue Exemple",
                            "city" => "Bordeaux",
                            "postal_code" => "33000",
                            "date" => "2026-03-31",
                            "time" => "19:00"
                        ],
                        "prices" => [
                            "menu_price" => 100.0,
                            "delivery_cost" => 0.0,
                            "total_price" => 100.0
                        ],
                        "status" => "en_attente",
                        "created_at" => "2024-11-01 12:00:00"
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Commande non trouvée ou accès non autorisé"
            )
        ]
    )]
    public function show(int $id, OrdersRepository $ordersRepository): JsonResponse
    {
        $order = $ordersRepository->find($id);
        if (!$order) {
            return $this->json(['error' => 'Commande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canAccessOrder($order)) {
            return $this->json(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $data = $this->serializer->serialize($order, 'json', ['groups' => ['orders:read', 'menu:read']]);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/list', methods: ['GET'], name: 'list')]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        tags: ["Orders"],
        summary: "Lister les commandes de l'utilisateur connecté",
        parameters: [
            new OA\Parameter(
                name: "status",
                in: "query",
                description: "Filtrer par statut (ex: en_attente, en_preparation, livre, annule)",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "createdAt",
                in: "query",
                description: "Filtrer par date de création (format YYYY-MM-DD)",
                required: false,
                schema: new OA\Schema(type: "string", format: "date")
            ),
            new OA\Parameter(
                name: "menu",
                in: "query",
                description: "Filtrer par ID de menu",
                required: false,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des commandes récupérée avec succès",
                content: new OA\JsonContent(
                    example: [
                        [
                            "id" => 1,
                            "menu" => "Menu Dégustation",
                            "number_of_people" => 15,
                            "delivery_info" => [
                                "address" => "123 Rue Exemple",
                                "city" => "Bordeaux",
                                "postal_code" => "33000",
                                "date" => "2026-03-31",
                                "time" => "19:00"
                            ],
                            "prices" => [
                                "menu_price" => 100.0,
                                "delivery_cost" => 0.0,
                                "total_price" => 100.0
                            ],
                            "status" => "en_attente",
                            "created_at" => "2024-11-01 12:00:00"
                        ]
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Requête invalide (ex: filtre non valide)"
            ),
            new OA\Response(
                response: 404,
                description: "Aucune commande trouvée pour l'utilisateur"
            )
        ]
    )]
    public function list(OrdersRepository $ordersRepository, Request $request): JsonResponse
    {
        $user = $this->getUser();
        $filters = [
            'status' => $request->query->get('status'),
            'user' => $user->getUserIdentifier(),
            'date' => $request->query->get('createdAt'),
            'menu' => $request->query->get('menu'),
        ];

        if ($filters['status']) {
            $statusEnum = Status::tryFrom($filters['status']);
            if (!$statusEnum) {
                return $this->json(['error' => 'Statut invalide'], Response::HTTP_BAD_REQUEST);
            }
            $filters['status'] = $statusEnum->value;
        }

        $orders = empty(array_filter($filters))
            ? $ordersRepository->findBy(['user' => $user])
            : $ordersRepository->findByFilters($filters);


        if (!$orders) {
            return $this->json(['message' => 'Aucune commande trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($orders, 'json', ['groups' => ['orders:read', 'menu:read']]);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // Méthodes auxiliaires
    #[Route('/{id}/cancel', methods: ['POST'], name: 'cancel')]
    #[IsGranted('ROLE_USER')]
    #[OA\Post(
        tags: ["Orders"],
        summary: "Annuler une commande",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la commande à annuler",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Commande annulée avec succès"
            ),
            new OA\Response(
                response: 400,
                description: "La commande ne peut être annulée à ce stade"
            ),
            new OA\Response(
                response: 403,
                description: "Accès non autorisé"
            ),
            new OA\Response(
                response: 404,
                description: "Commande non trouvée"
            )
        ]
    )]
    public function cancel(Orders $order): JsonResponse
    {
        if (!$this->canAccessOrder($order)) {
            return $this->json(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }
        //
        if (!in_array($order->getStatus(), [Status::en_attente->value])) {
            return $this->json(['error' => 'La commande ne peut être annulée à ce stade'], Response::HTTP_BAD_REQUEST);
        }

        $order->setStatus(Status::annule->value);
        $this->entityManager->flush();

        return $this->json(['message' => 'Commande annulée avec succès'], Response::HTTP_OK);
    }
    #[Route('/{id}/edit', methods: ['PUT'], name: 'edit')]
    #[IsGranted('ROLE_USER')]
    #[OA\Put(
        tags: ["Orders"],
        summary: "Modifier une commande",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la commande à modifier",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            description: "Détails à modifier (ex: numberOfPeople, deliveryAddress, etc.)",
            required: true,
            content: new OA\JsonContent(
                example: [
                    "numberOfPeople" => 20,
                    "deliveryAddress" => "456 Rue Modifiée",
                    "deliveryCity" => "Bordeaux",
                    "deliveryPostalCode" => "33000",
                    "deliveryDate" => "2026-04-01",
                    "deliveryTime" => "20:00"
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Commande modifiée avec succès"
            ),
            new OA\Response(
                response: 400,
                description: "La commande ne peut être modifiée à ce stade ou données invalides"
            ),
            new OA\Response(
                response: 403,
                description: "Accès non autorisé"
            ),
            new OA\Response(
                response: 404,
                description: "Commande non trouvée"
            )
        ]
    )]
    public function edit(Orders $order, Request $request): JsonResponse
    {
        if (!$this->canAccessOrder($order)) {
            return $this->json(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }
        if (!in_array($order->getStatus(), [Status::en_attente->value])) {
            return $this->json(['error' => 'La commande ne peut être modifiée à ce stade'], Response::HTTP_BAD_REQUEST);
        } else {
            // Logique de modification de la commande (ex: changer le nombre de personnes, l'adresse de livraison, etc.)
            $data = json_decode($request->getContent(), true);
            if (isset($data['numberOfPeople'])) {
                $order->setNumberOfPeople($data['numberOfPeople']);
            }
            if (isset($data['deliveryAddress'])) {
                $order->setDeliveryAddress($data['deliveryAddress']);
            }
            if (isset($data['deliveryCity'])) {
                $order->setDeliveryCity($data['deliveryCity']);
            }
            if (isset($data['deliveryPostalCode'])) {
                $order->setDeliveryPostalCode($data['deliveryPostalCode']);
            }
            if (isset($data['deliveryDate'])) {
                $order->setDeliveryDate(new \DateTime($data['deliveryDate']));
            }
            if (isset($data['deliveryTime'])) {
                $order->setDeliveryTime(new \DateTime($data['deliveryTime']));
            }
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            return $this->json(['message' => 'Commande modifiée avec succès'], Response::HTTP_OK);
        }
    }


    // Logique de modification de la commande (ex: changer le nombre de personnes, l'adresse de livraison


    private function canAccessOrder(Orders $order): bool
    {
        $user = $this->getUser();
        return $user && ($user->getId() === $order->getUser()->getId() || $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_EMPLOYEE'));
    }


    private function calculateDeliveryCost(string $deliveryCity, string $deliveryPostalCode): float
    {
        $bordeauxPostalCodes = ['33000', '33100', '33200', '33300', '33400', '33500', '33800'];
        return in_array($deliveryPostalCode, $bordeauxPostalCodes) && stripos($deliveryCity, 'Bordeaux') !== false
            ? 0.0
            : 5.0;
    }

    private function discount(Orders $orders): float
    {
        $numberOfPeople = $orders->getNumberOfPeople();
        return $numberOfPeople > $orders->getMenu()->getMinPeople() + 5
            ? 0.1 * $orders->getMenu()->getPrice() * $numberOfPeople
            : 0.0;
    }
}
