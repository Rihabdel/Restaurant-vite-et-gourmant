<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Entity\Menus;
use App\Entity\User;
use App\Enum\Status;
use App\Repository\OrdersRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use DateTimeImmutable;
use Doctrine\ORM\Mapping\Id;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use symfony\component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('api/orders', name: 'app_api_orders_')]
final class OrdersController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
        $id = null;
    }

    #[Route('/new', methods: ['POST'], name: 'new')]
    public function new(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            $data = json_decode($request->getContent(), true);
            // verifier que les données nécessaires sont présentes dans la requete
            if (
                !isset($data['menu']) || !isset($data['numberOfPeople']) ||
                !isset($data['deliveryAddress']) || !isset($data['deliveryDate']) || !isset($data['deliveryTime']) || !isset($data['deliveryCity']) || !isset($data['deliveryPostalCode'])
            ) {
                return new JsonResponse(
                    ['error' => 'Données manquantes'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            // verifier que le menu existe
            $menu = $this->entityManager->getRepository(Menus::class)->find($data['menu']);
            if (!$menu) {
                return new JsonResponse(
                    ['error' => 'menu non trouvé'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            // verifier le nombre de personnes
            if ($data['numberOfPeople'] < $menu->getMinPeople()) {
                return new JsonResponse(
                    ['error' => 'Minimum {' . $menu->getMinPeople() . '} personnes requises pour ce menu'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // verifier le stock du menu
            if ($data['numberOfPeople'] > $menu->getStock()) {
                return new JsonResponse(
                    ['error' => 'Stock insuffisant pour ce menu.Disponible pour : ' . $menu->getStock() . ' personnes'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            // verifier la date de livraison si elle est superieur à la date actuelle et si elle respecte le délai de préparation du menu
            $deliveryDateTime = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $data['deliveryDate'] . ' ' . $data['deliveryTime'] . ':00'
            );
            if (!$deliveryDateTime) {
                return new JsonResponse(
                    ['error' => 'Format de date ou d\'heure invalide. La date doit être au format YYYY-MM-DD et l\'heure au format HH:MM'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $currentDate = new DateTimeImmutable();
            if ($deliveryDateTime < $currentDate) {
                return new JsonResponse(
                    ['error' => 'La date de livraison doit être supérieure à la date actuelle'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            // verifier le délai de préparation du menu
            if ($deliveryDateTime < $currentDate->modify('+' . $menu->getConditions() . ' hours')) {
                return new JsonResponse(
                    ['error' => 'La date de livraison doit respecter le délai de préparation du menu qui est de ' . $menu->getConditions() . ' heures'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            // calculer le coût de livraison en fonction de la ville de livraison
            $deliveryCost = $this->calculateDeliveryCost(
                $data['deliveryCity'],
                $data['deliveryPostalCode']
            );


            // créer la commande
            $orders = new Orders();
            $orders->setMenu($menu);
            $orders->setUser($user);
            $orders->setNumberOfPeople($data['numberOfPeople']);
            $orders->setDeliveryAddress($data['deliveryAddress']);
            $orders->setDeliveryDate(new \DateTime($data['deliveryDate']));
            $orders->setDeliveryTime(new \DateTime($data['deliveryTime']));
            $orders->setDeliveryCity($data['deliveryCity']);
            $orders->setDeliveryAddress($data['deliveryAddress']);
            $orders->setDeliveryPostalCode($data['deliveryPostalCode']);
            $orders->setCreatedAt(new \DateTimeImmutable());

            $orders->setDeliveryCost($deliveryCost);
            $totalPrice = $menu->calculate_total_price($data['numberOfPeople']) + $deliveryCost;

            $orders->setTotalPrice($totalPrice);
            $orders->setStatus('pending');

            // Valider l'entité
            $errors = $this->validator->validate($orders);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse(
                    ['errors' => $errorMessages],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Mettre à jour le stock
            $menu->setStock($menu->getStock() - $data['numberOfPeople']);

            // Sauvegarder
            $this->entityManager->persist($orders);
            $this->entityManager->flush();

            // Préparer la réponse
            $responseData = [
                'id' => $orders->getId(),
                'menu' => $menu->getTitle(),
                'number_of_people' => $orders->getNumberOfPeople(),
                'delivery_info' => [
                    'address' => $orders->getDeliveryAddress(),
                    'city' => $orders->getDeliveryCity(),
                    'postal_code' => $orders->getDeliveryPostalCode(),
                    'date' => $orders->getDeliveryDate()->format('Y-m-d'),
                    'time' => $orders->getDeliveryTime()->format('H:i'),
                ],
                'prices' => [
                    'menu_price' => $menu->getPrice(),
                    'delivery_cost' => $deliveryCost,
                    'total_price' => $totalPrice,
                ],
                'status' => $orders->getStatus(),
                'created_at' => $orders->getCreatedAt()->format('Y-m-d H:i:s'),
            ];

            $location = $this->generateUrl(
                'api_orders_show',
                ['id' => $orders->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            return new JsonResponse(
                $responseData,
                Response::HTTP_CREATED,
                ['Location' => $location]
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la création de la commande: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    #[Route('/{id}', methods: ['GET'], name: 'api_orders_show')]
    public function show(int $id, OrdersRepository $ordersRepository): Response

    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(
                ['message' => 'Utilisateur non authentifié'],
                Response::HTTP_UNAUTHORIZED
            );
        }
        $orders = $ordersRepository->find($id);
        if (!$orders) {
            return new JsonResponse(
                ['message' => 'commande non trouvée'],
                Response::HTTP_NOT_FOUND
            );
        }
        // vérifier que l'utilisateur est le propriétaire de la commande ou qu'il a le rôle admin ou employee
        $custyomerId = $orders->getUser()->getId();
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_EMPLOYEE') && $user->getId() !== $custyomerId) {
            return new JsonResponse(
                ['message' => 'Access non autorisé'],
                Response::HTTP_FORBIDDEN
            );
        }
        // sérialiser la commande avec les groupes de sérialisation appropriés
        $data = $this->serializer->serialize($orders, 'json', ['groups' => ['orders:read', 'menu:read']]);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
    #[Route('/', methods: ['GET'], name: 'api_orders_list')]
    public function list(OrdersRepository $ordersRepository, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(
                ['message' => 'Utilisateur non authentifié'],
                Response::HTTP_UNAUTHORIZED
            );
        }
        $userId = $user->entityManager->getRepository(User::class)->find($user)->getId();
        $orders = $ordersRepository->findByUser($userId);
        if (!$orders) {
            return new JsonResponse(
                ['message' => 'Aucune commande trouvée'],
                Response::HTTP_NOT_FOUND
            );
        }
        $filters = [
            'status' => $request->query->get('status'),
            'utilisateur' => $request->query->get('user'),
            'date' => $request->query->get('createdAt'),
            'menu' => $request->query->get('menu'),
        ];
        if (count($filters) === 0) {
            $orders = $ordersRepository->findAll();
        } else {
            $orders = $ordersRepository->findByFilters($filters);
        }
        error_log("=== METHODE findAll UTILISEE ===");
        error_log("Nombre de résultats: " . count($orders));
        $data = $this->serializer->serialize($orders, 'json', ['groups' => ['orders:read', 'menu:read']]);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
        //convertir enum status en string dans la réponse
        if (isset($filters['status'])) {
            $statusEnum = Status::tryFrom($filters['status']);
            if ($statusEnum) {
                $filters['status'] = $statusEnum->value;
            } else {
                return new JsonResponse(
                    ['error' => 'Statut invalide. Les statuts valides sont: en attente, accepté, en préparation, livraison, en attente de retour, terminé, annulé'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $orders = $ordersRepository->findByFilters($filters);
            $data = $this->serializer->serialize($orders, 'json', ['groups' => ['orders:read', 'menu:read']]);
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        }
        $reasponseData = [
            'id' => $orders->getId(),
            'menu' => $orders->getMenu()->getTitle(),
            'number_of_people' => $orders->getNumberOfPeople(),
            'prices' => [
                'menu_price' => $orders->getMenu()->getPrice(),
                'delivery_cost' => $orders->getDeliveryCost(),
                'total_price' => $orders->getTotalPrice(),
            ],
            'status' => $orders->getStatus(),
            'created_at' => $orders->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
        return new JsonResponse($reasponseData, Response::HTTP_OK);
    }
    #[Route('/api/orders/{id}/cancel', name: 'api_orders_cancel', methods: ['DELETE'])]
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getUser();
            $orders = $this->entityManager->getRepository(Orders::class)->find($id);

            if (!$orders) {
                return new JsonResponse(
                    ['error' => 'Commande non trouvée'],
                    Response::HTTP_NOT_FOUND
                );
            }

            // vérifier que l'utilisateur est le propriétaire de la commande ou qu'il a le rôle admin ou employee
            $custyomerId = $orders->getUser()->getId();
            if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_EMPLOYEE') && $user->getId() !== $custyomerId) {
                return new JsonResponse(
                    ['message' => 'Access non autorisé'],
                    Response::HTTP_FORBIDDEN
                );
            }
            // Vérifier si la commande peut être annulée
            if ($orders->getStatus() !== 'pending' && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_EMPLOYEE')) {
                return new JsonResponse(
                    ['error' => 'Vous ne pouvez annuler que les commandes en attente'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $data = json_decode($request->getContent(), true);

            // Les employés doivent fournir une raison
            if (($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_EMPLOYEE')) && empty($data['canceledReason'])) {
                return new JsonResponse(
                    ['error' => 'La raison d\'annulation est requise pour les employés'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Enregistrer la raison d'annulation
            if (($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_EMPLOYEE')) && isset($data['canceledReason'])) {
                $orders->setCancellationReason($data['canceledReason']);
            } elseif ($user->getId() === $custyomerId) {
                $orders->setCancellationReason('Annulé par le client');
            }

            // Restituer le stock
            $menu = $orders->getMenu();
            $menu->setStock($menu->getStock() + $orders->getNumberOfPeople());

            // Mettre à jour le statut
            $oldStatus = $orders->getStatus();
            $orders->setStatus('annulé');
            $orders->setUpdatedAt(new \DateTimeImmutable());

            // Sauvegarder
            $this->entityManager->flush();

            // TODO: Envoyer un email de notification
            // $this->emailService->sendCancellationEmail($orders, $user);

            return new JsonResponse([
                'message' => 'Commande annulée avec succès',
                'order_id' => $orders->getId(),
                'old_status' => $oldStatus,
                'new_status' => 'annulé',
                'stock_restored' => $orders->getNumberOfPeople()
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Erreur lors de l\'annulation: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    //modifier la commande (un client peut modifier sa commande tant qu'elle est en attente et non acceptée par un employé, un employé ou admin peut modifier une commande tant qu'elle n'est pas livrée)

    #[Route('/{id}/edit', methods: ['PUT'], name: 'api_orders_edit')]
    public function edit(Request $request, int $id): JsonResponse

    {
        $user = $this->getUser();
        $orders = $this->entityManager->getRepository(Orders::class)->find($id);
        if (!$orders) {
            return new JsonResponse(
                ['error' => 'Commande non trouvée'],
                Response::HTTP_NOT_FOUND
            );
        }
        // vérifier que l'utilisateur est le propriétaire de la commande ou qu'il a le rôle admin ou employee
        $custyomerId = $orders->getUser()->getId();
        $usrId = $user->getId();
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_EMPLOYEE') && $usrId !== $custyomerId) {
            return new JsonResponse(
                ['message' => 'Access non autorisé'],
                Response::HTTP_FORBIDDEN
            );
        }
        // vérifier que la commande peut être modifiée
        if ($orders->getStatus() == 'pending' && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_EMPLOYEE')) {
            // le client peut modifier sa commande tant qu'elle est en attente et non acceptée par un employé
            $data = json_decode($request->getContent(), true);
            // vérifier que les données nécessaires sont présentes dans la requete
            if (
                !isset($data['numberOfPeople']) ||
                !isset($data['deliveryAddress']) || !isset($data['deliveryDate']) || !isset($data['deliveryTime']) || !isset($data['deliveryCity']) || !isset($data['deliveryPostalCode'])
            ) {
                return new JsonResponse(
                    ['error' => 'Données manquantes'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            // vérifier le nombre de personnes
            if ($data['numberOfPeople'] < $orders->getMenu()->getMinPeople()) {
                return new JsonResponse(
                    ['error' => 'Minimum {' . $orders->getMenu()->getMinPeople() . '} personnes requises pour ce menu'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            // vérifier le stock du menu
            if ($data['numberOfPeople'] > $orders->getMenu()->getStock()) {
                return new JsonResponse(
                    ['error' => 'Stock insuffisant pour ce menu.Disponible pour : ' . $orders->getMenu()->getStock() . ' personnes'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            // vérifier la date de livraison si elle est superieur à la date actuelle et si elle respecte le délai de préparation du menu
            $deliveryDateTime = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $data['deliveryDate'] . ' ' . $data['deliveryTime'] . ':00'
            );
            if (!$deliveryDateTime) {
                return new JsonResponse(
                    ['error' => 'Format de date ou d\'heure invalide. La date doit être au format YYYY-MM-DD et l\'heure au format HH:MM'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $currentDate = new DateTimeImmutable();
            if ($deliveryDateTime < $currentDate) {
                return new JsonResponse(
                    ['error' => 'La date de livraison doit être supérieure à la date actuelle'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            // vérifier le délai de préparation du menu
            if ($deliveryDateTime < $currentDate->modify('+' . $orders->getMenu()->getConditions() . ' hours')) {
                return new JsonResponse(
                    ['error' => 'La date de livraison doit respecter le délai de préparation du menu qui est de ' . $orders->getMenu()->getConditions() . ' heures'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            // mettre à jour la commande
            $orders->setNumberOfPeople($data['numberOfPeople']);
            $orders->setDeliveryAddress($data['deliveryAddress']);
            $orders->setDeliveryDate(new \DateTime($data['deliveryDate']));
            $orders->setDeliveryTime(new \DateTime($data['deliveryTime']));
            $orders->setDeliveryCity($data['deliveryCity']);
            $orders->setDeliveryPostalCode($data['deliveryPostalCode']);
            $orders->setUpdatedAt(new \DateTimeImmutable());
            // calculer le coût de livraison en fonction de la ville de livraison
            $deliveryCost = $this->calculateDeliveryCost(
                $data['deliveryCity'],
                $data['deliveryPostalCode']
            );

            $orders->setDeliveryCost($deliveryCost);
            $numberOfPeople = $data['numberOfPeople'];
            $orders->setTotalPrice($orders->getMenu()->calculate_total_price($numberOfPeople) + $deliveryCost);
            $totalPrice = $orders->getMenu()->calculate_total_price($numberOfPeople) + $deliveryCost;
            // sauvegarder

            $this->entityManager->flush();
            return new JsonResponse(
                [
                    'message' => 'Commande modifiée avec succès',
                    'order_id' => $orders->getId(),
                    'number_of_people' => $orders->getNumberOfPeople(),
                    'delivery_info' => [
                        'address' => $orders->getDeliveryAddress(),
                        'city' => $orders->getDeliveryCity(),
                        'postal_code' => $orders->getDeliveryPostalCode(),
                        'date' => $orders->getDeliveryDate()->format('Y-m-d'),
                        'time' => $orders->getDeliveryTime()->format('H:i'),
                    ],
                    'prices' => [
                        'menu_price' => $orders->getMenu()->getPrice(),
                        'delivery_cost' => $deliveryCost,
                        'total_price' => $totalPrice,
                        'discount' => $this->discount($orders),
                    ],
                    'status' => $orders->getStatus(),
                    'updated_at' => $orders->getUpdatedAt()->format('Y-m-d H:i:s'),
                ],
                Response::HTTP_OK
            );
        } else if (($orders->getStatus() == 'pending' || $orders->getStatus() == 'accepté' || $orders->getStatus() == 'en préparation') && ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_EMPLOYEE'))) {
            // un employé ou admin peut modifier une commande tant qu'elle n'est pas livrée
            $data = json_decode($request->getContent(), true);
            // vérifier que les données nécessaires sont présentes dans la requete
            if (
                !isset($data['status'])
            ) {
                return new JsonResponse(
                    ['error' => 'Données manquantes'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            // vérifier que le statut est valide
            $validStatuses = ['en attente', 'accepté', 'en préparation', 'livraison', 'en attente de retour', 'terminé', 'annulé'];
            if (!in_array($data['status'], $validStatuses)) {
                return new JsonResponse(
                    ['error' => 'Statut invalide. Les statuts valides sont: ' . implode(', ', $validStatuses)],
                    Response::HTTP_BAD_REQUEST
                );
            }
            // mettre à jour le statut de la commande
            $orders->setUpdatedAt(new \DateTimeImmutable());
            $orders->setStatus($data['status']);
            // sauvegarder
            $this->entityManager->flush();
            // Préparer la réponse
            $responseData = [
                'id' => $orders->getId(),
                'number_of_people' => $orders->getNumberOfPeople(),
                'delivery_info' => [
                    'address' => $orders->getDeliveryAddress(),
                    'city' => $orders->getDeliveryCity(),
                    'postal_code' => $orders->getDeliveryPostalCode(),
                    'date' => $orders->getDeliveryDate()->format('Y-m-d'),
                    'time' => $orders->getDeliveryTime()->format('H:i'),
                ],
                'prices' => [
                    'menu_price' => $orders->getMenu()->getPrice(),
                    'delivery_cost' => $orders->getDeliveryCost(),
                    'total_price' => $orders->getTotalPrice(),
                ],
                'status' => $orders->getStatus(),
                'created_at' => $orders->getCreatedAt()->format('Y-m-d H:i:s'),
            ];

            $location = $this->generateUrl(
                'api_orders_show',
                ['id' => $orders->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            return new JsonResponse(
                $responseData,
                Response::HTTP_CREATED,
                ['Location' => $location]
            );
        } else {
            return new JsonResponse(
                ['error' => 'Vous ne pouvez modifier cette commande à ce stade'],
                Response::HTTP_BAD_REQUEST
            );
        }
    }
    private function calculateDeliveryCost(string $deliveryCity, string $deliveryPostalCode): float
    {
        $bordeauxPostalCodes = ['33000', '33100', '33200', '33300', '33400', '33500', '33800'];
        if (in_array($deliveryPostalCode, $bordeauxPostalCodes) && stripos($deliveryCity, 'Bordeaux') !== false) {
            return 0.0; // Coût de livraison pour Bordeaux
        } else {
            return 5.0; // Coût de livraison pour les autres villes
        }
    }
    public function discount(Orders $orders): float
    {
        $numberOfPeople = $orders->getNumberOfPeople();
        if ($numberOfPeople > $orders->getMenu()->getMinPeople() + 5) {
            return 0.1 * $orders->getMenu()->getPrice() * $numberOfPeople; // 10% de réduction
        } else {
            return 0.0; // Pas de réduction
        }
    }
}
