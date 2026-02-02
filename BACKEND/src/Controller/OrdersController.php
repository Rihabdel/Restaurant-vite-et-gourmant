<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Repository\OrdersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use DateTimeImmutable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use symfony\component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
            $orders = $this->serializer->deserialize(
                $request->getContent(),
                Orders::class,
                'json'
            );
            $errors = $this->validator->validate($orders);
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
            $orders->setCreatedAt(new DateTimeImmutable());
            $this->entityManager->persist($orders);
            $this->entityManager->flush();
            $responseData = $this->serializer->serialize($orders, 'json');
            $location = $this->generateUrl(
                'app_api_orders_show',
                ['id' => $orders->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            return new JsonResponse($responseData, Response::HTTP_CREATED, [
                'Location' => $location
            ], true);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }


    #[Route('/{id}', methods: ['GET'], name: 'show')]
    public function show(int $id, OrdersRepository $ordersRepository): Response

    {
        $orders = $ordersRepository->find($id);
        if (!$orders) {
            return new JsonResponse(
                ['message' => 'Orders not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        $responseData = $this->serializer->serialize($orders, 'json');
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['PUT'], name: 'edit')]
    public function edit(EntityManagerInterface $entityManager, int $id): Response
    {
        $orders = $entityManager->getRepository(Orders::class)->find($id);
        if (!$orders) {
            return new JsonResponse(
                ['message' => 'Orders not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        $orders->setUpdatedAt(new DateTimeImmutable());
        $orders = $this->serializer->deserialize(
            file_get_contents('php://input'),
            Orders::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $orders]
        );

        $entityManager->persist($orders);
        $entityManager->flush();
        $responseData = $this->serializer->serialize($orders, 'json');
        $location = $this->generateUrl(
            'app_api_orders_show',
            ['id' => $orders->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return new JsonResponse($responseData, Response::HTTP_OK, ['Location' => $location], true);
    }
    #[Route('/{id}', methods: ['DELETE'], name: 'delete')]
    public function delete(EntityManagerInterface $entityManager, int $id): Response
    {

        $orders = $entityManager->getRepository(Orders::class)->find($id);
        if (!$orders) {
            return new JsonResponse(
                ['message' => 'Orders not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        $entityManager->remove($orders);
        $entityManager->flush();
        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }
}
