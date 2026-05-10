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
use App\Service\MailService;

#[Route('api', name: 'app_api_orders_')]
final class OrdersController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private MailService $mailService
    ) {}

    #[Route('/orders/new', methods: ['POST'], name: 'new')]
    #[IsGranted('ROLE_USER')]
    #[OA\Post(
        tags: ["Orders"],
        summary: "Créer une nouvelle commande",
        requestBody: new OA\RequestBody(
            description: "Détails de la commande à créer",
            required: true,
            content: new OA\JsonContent(
                example: [
                    "menu" => 1,
                    "numberOfPeople" => 30,
                    "deliveryAddress" => "123 Rue Exemple",
                    "deliveryCity" => "Bordeaux",
                    "deliveryPostalCode" => "33000",
                    "deliveryDate" => "2026-07-15",
                    "deliveryTime" => "19:00"
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Commande créée avec succès"
            ),
            new OA\Response(
                response: 400,
                description: "Données invalides ou contraintes non respectées"
            ),
            new OA\Response(
                response: 401,
                description: "Utilisateur non authentifié"
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

            $deliveryCost = $this->calculateDeliveryCost(
                $data['deliveryCity'],
                $data['deliveryPostalCode'],
                10.0
            ); //en attendant d'avoir une vraie logique de calcul de distance.

            $totalPrice =
                $menu->getPriceEstimate($data['numberOfPeople'])
                + $deliveryCost;

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
                ->setTotalPrice($totalPrice)
                ->setStatus(Status::en_attente->value)

            ;
            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $menu->setStock($menu->getStock() - $data['numberOfPeople']);
            $this->entityManager->flush();
            $this->mailService->sendOrderConfirmation($order);


            return $this->json(
                $order,
                Response::HTTP_CREATED,
                [],
                ['groups' => ['orders:read', 'menu:read']]
            );
        } catch (\Exception $e) {
            return $this->json(['error' => 'Une erreur est survenue lors de la création de la commande : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route(
        '/orders/{id}',
        name: 'orders_show',
        methods: ['GET'],
        requirements: ['id' => '\d+']
    )]
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
                description: "Détails de la commande récupérés avec succès"
            ),
            new OA\Response(
                response: 403,
                description: "Accès non autorisé à cette commande"
            ),
            new OA\Response(
                response: 404,
                description: "Commande non trouvée"
            )
        ]
    )]
    #[Route('/orders/preview', methods: ['POST'], name: 'preview')]
    #[IsGranted('ROLE_USER')]
    public function preview(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $menu = $this->entityManager
            ->getRepository(Menus::class)
            ->find($data['menu']);

        if (!$menu) {
            return $this->json(['error' => 'Menu introuvable'], 404);
        }

        $deliveryCost = $this->calculateDeliveryCost(
            $data['deliveryCity'],
            $data['deliveryPostalCode'],
            10.0
        );

        $totalPrice =
            $menu->getPriceEstimate($data['numberOfPeople'])
            + $deliveryCost;

        $discount = $this->discount((new Orders())->setNumberOfPeople($data['numberOfPeople'])->setMenu($menu));

        return $this->json([
            'menuPrice' => $menu->getPrice(),
            'totalPrice' => $totalPrice,
            'deliveryCost' => $deliveryCost,
            'discount' => $discount
        ]);
    }
    // Afficher les détails d'une commande (accessible uniquement par le propriétaire de la commande, les admins et les employés)
    public function show(int $id, OrdersRepository $ordersRepository): JsonResponse
    {
        $order = $ordersRepository->find($id);
        if (!$order) {
            return $this->json(['error' => 'Commande non trouvée'], Response::HTTP_NOT_FOUND);
        }
        if (!$this->canAccessOrder($order)) {
            return $this->json(['error' => 'Accès non autorisé à cette commande'], Response::HTTP_FORBIDDEN);
        }
        return $this->json($order, Response::HTTP_OK, [], ['groups' => ['orders:read', 'menu:read']]);
    }

    // Lister les commandes d'un utilisateur
    #[Route('/orders', methods: ['GET'], name: 'listOrdersByUser')]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        tags: ["Orders"],
        summary: "Lister les commandes de l'utilisateur connecté",
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des commandes récupérée avec succès"
            ),
            new OA\Response(
                response: 401,
                description: "Utilisateur non authentifié"
            ),
            new OA\Response(
                response: 404,
                description: "Aucune commande trouvée pour cet utilisateur"
            )
        ]
    )]
    public function listByUser(OrdersRepository $ordersRepository): JsonResponse

    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }
        $orders = $ordersRepository->findByClientId($user->getId());
        if ($orders === []) {
            return $this->json(['message' => 'Aucune commande trouvée pour cet utilisateur'], Response::HTTP_NOT_FOUND);
        }
        if (!$orders) {
            return $this->json(['message' => 'Aucune commande trouvée pour cet utilisateur'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($orders, Response::HTTP_OK, [], ['groups' => ['orders:read', 'menu:read', 'user:read']]);
    }

    // Méthodes auxiliaires
    #[Route('/orders/{id}/cancel', methods: ['PUT'], name: 'cancel')]
    #[IsGranted('ROLE_USER')]
    #[OA\Put(
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

        $order->setStatus(Status::annulé->value);
        $this->entityManager->flush();

        return $this->json(['message' => 'Commande annulée avec succès'], Response::HTTP_OK);
    }
    #[Route('/orders/{id}/edit', methods: ['PUT'], name: 'edit')]
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
            //si admininistrateur ou employé, possibilité de modifier le statut de la commande
            if (isset($data['status']) && (in_array('ROLE_ADMIN', $this->getUser()->getRoles()) || in_array('ROLE_EMPLOYE', $this->getUser()->getRoles()))) {
                $statusEnum = Status::tryFrom($data['status']);
                if (!$statusEnum) {
                    return $this->json(['error' => 'Statut invalide'], Response::HTTP_BAD_REQUEST);
                }
                $order->setStatus($statusEnum->value);
            }
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            return $this->json(['message' => 'Commande modifiée avec succès'], Response::HTTP_OK);
        }
    }


    // Logique de modification de la commande (ex: changer le nombre de personnes, l'adresse de livraison


    private function canAccessOrder(Orders $order): bool
    {
        // Seul le propriétaire de la commande, les admins et les employés peuvent accéder aux détails d'une commande
        $user = $this->getUser();
        return $user instanceof User && ($order->getUser()->getId() === $user->getId() || in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_EMPLOYE', $user->getRoles()));
    }


    private function calculateDeliveryCost(string $deliveryCity, string $deliveryPostalCode, float $distance): float
    {

        $bordeauxPostalCodes = ['33000', '33100', '33200', '33300', '33400', '33500', '33800'];
        $isBordeaux =
            in_array($deliveryPostalCode, $bordeauxPostalCodes) &&
            stripos($deliveryCity, 'bordeaux') !== false;

        if ($isBordeaux) {
            return 5.0;
        }
        return 5.0 + ($distance * 0.59);
    }
    private function discount(Orders $orders): float
    {
        $numberOfPeople = $orders->getNumberOfPeople();
        return $numberOfPeople > $orders->getMenu()->getMinPeople() + 5
            ? 0.1 * $orders->getMenu()->getPrice() * $numberOfPeople
            : 0.0;
    }
    //delete menu admin ou employee
    #[Route('/admin/orders/{id}', methods: ['DELETE'], name: 'delete')]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        tags: ["Orders"],
        summary: "Supprimer une commande",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la commande à supprimer",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Commande supprimée avec succès"
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
    public function delete(Orders $order): JsonResponse
    {
        if (!$this->canAccessOrder($order)) {
            return $this->json(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }
        $this->entityManager->remove($order);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    // afficher toutes les commandes pour les employés et les admins et les filtrer par statut, date de livraison ou menu
    #[Route('/admin/orders', methods: ['GET'], name: 'admin_index')]
    #[IsGranted('ROLE_ADMIN')]

    #[OA\Get(
        tags: ["Orders"],
        summary: "Lister toutes les commandes (admin/employee)",
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des commandes récupérée avec succès"
            ),
            new OA\Response(
                response: 403,
                description: "Accès non autorisé"
            )
        ]
    )]
    public function adminIndex(OrdersRepository $ordersRepository, SerializerInterface $serializer, Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->query->get('status'),
            'user' => $request->query->get('user'),
            'delivery_date' => $request->query->get('delivery_date'),
            'menu' => $request->query->get('menu'),
        ];

        if ($filters['status']) {
            $statusEnum = Status::tryFrom($filters['status']);
            if (!$statusEnum) {
                return $this->json(['error' => 'Statut invalide'], Response::HTTP_BAD_REQUEST);
            }
            $filters['status'] = $statusEnum->value;
        }
        if ($filters['user']) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['lastName' => $filters['user']]);
            if (!$user) {
                return $this->json(['error' => 'Utilisateur non trouvé pour le filtre'], Response::HTTP_BAD_REQUEST);
            }
            $filters['user'] = $user->getId();
        }
        if ($filters['delivery_date']) {
            try {
                $filters['delivery_date'] = new \DateTime($filters['delivery_date']);
            } catch (\Exception $e) {
                return $this->json(['error' => 'Date de livraison invalide pour le filtre'], Response::HTTP_BAD_REQUEST);
            }
        }
        if ($filters['menu']) {
            $menu = $this->entityManager->getRepository(Menus::class)->findOneBy(['title' => $filters['menu']]);
            if (!$menu) {
                return $this->json(['error' => 'Menu non trouvé pour le filtre'], Response::HTTP_BAD_REQUEST);
            }
            $filters['menu'] = $menu->getId();
        }

        $orders = empty(array_filter($filters))
            ? $ordersRepository->findAll()
            : $ordersRepository->findByFilters($filters);

        if (!$orders) {
            return $this->json(['message' => 'Aucune commande trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($orders, 'json', ['groups' => ['orders:read', 'menu:read']]);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // Modifier une commande par un admin ou un employé
    #[Route('/admin/orders/{id}/edit', methods: ['PUT'], name: 'admin_edit')]
    #[IsGranted('ROLE_ADMIN')]

    public function editOrderByAdmin(Orders $order, Request $request): JsonResponse
    {
        if (!in_array($order->getStatus(), [Status::en_attente->value, Status::en_préparation->value])) {
            return $this->json(['error' => 'La commande ne peut être annulée à ce stade'], Response::HTTP_BAD_REQUEST);
        }
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
        if (isset($data['status'])) {
            $statusEnum = Status::tryFrom($data['status']);
            if (!$statusEnum) {
                return $this->json(['error' => 'Statut invalide'], Response::HTTP_BAD_REQUEST);
            }
            $order->setStatus($statusEnum->value);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();
        return $this->json(['message' => 'Commande modifiée avec succès'], Response::HTTP_OK);
    }


    // Mettre à jour le statut d'une commande par un admin
    #[Route('/admin/orders/{id}/status', methods: ['PUT'], name: 'admin_update_status')]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(
        tags: ["Orders"],
        summary: "Mettre à jour le statut d'une commande (admin)",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la commande à mettre à jour",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            description: "Nouveau statut de la commande",
            required: true,
            content: new OA\JsonContent(
                example: [
                    "status" => "en_preparation"
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Statut de la commande mis à jour avec succès"
            ),
            new OA\Response(
                response: 400,
                description: "Statut invalide ou mise à jour non autorisée à ce stade"
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
    public function updateOrderStatus(Orders $order, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['status'])) {
            return $this->json(['error' => 'Statut manquant'], Response::HTTP_BAD_REQUEST);
        }

        $statusEnum = Status::tryFrom($data['status']);
        if (!$statusEnum) {
            return $this->json(['error' => 'Statut invalide'], Response::HTTP_BAD_REQUEST);
        }

        $order->setStatus($statusEnum->value);
        $this->entityManager->flush();

        return $this->json(['message' => 'Statut de la commande mis à jour avec succès'], Response::HTTP_OK);
    }
}
