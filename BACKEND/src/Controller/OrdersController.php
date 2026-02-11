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
        summary: "Créer une nouvelle commande"
    )]
    public function new(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            $data = json_decode($request->getContent(), true);

            $requiredFields = [
                'menu',
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

            if ($deliveryDateTime < $currentDate->modify('+' . $menu->getConditions() . ' hours')) {
                return $this->json(['error' => "La date de livraison doit respecter le délai de préparation ({$menu->getConditions()} heures)"], Response::HTTP_BAD_REQUEST);
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
                ->setStatus(Status::PENDING->value);

            $errors = $this->validator->validate($order);
            if (count($errors) > 0) {
                $messages = array_map(fn($e) => $e->getMessage(), iterator_to_array($errors));
                return $this->json(['errors' => $messages], Response::HTTP_BAD_REQUEST);
            }

            // Mettre à jour le stock
            $menu->setStock($menu->getStock() - $data['numberOfPeople']);

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            $location = $this->generateUrl('api_orders_show', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

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

    #[Route('/', methods: ['GET'], name: 'list')]
    #[IsGranted('ROLE_USER')]
    public function list(OrdersRepository $ordersRepository, Request $request): JsonResponse
    {
        $user = $this->getUser();
        $userId = $user->getId();

        $filters = [
            'status' => $request->query->get('status'),
            'user' => $userId,
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
            ? $ordersRepository->findByUserId($userId)
            : $ordersRepository->findByFilters($filters);

        if (!$orders) {
            return $this->json(['message' => 'Aucune commande trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($orders, 'json', ['groups' => ['orders:read', 'menu:read']]);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // Méthodes privées

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
