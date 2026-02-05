<?php

namespace App\Controller;


use App\Entity\Allergens;
use App\Repository\AllergensRepository;
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

#[Route('api/allergens', name: 'app_api_allergens_')]
final class AllergensController extends AbstractController
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
            $data = json_decode($request->getContent(), true);

            // Validation des champs obligatoires
            if (!isset($data['name']) || empty($data['description'])) {
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
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la création de l\'allergène: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    #[Route('/{id}', methods: ['GET'], name: 'show')]
    public function show(int $id, AllergensRepository $allergensRepository): Response

    {
        try {
            $allergen = $allergensRepository->find($id);
            $allergen = $allergensRepository->find($id);
            if (!$allergen) {
                return new JsonResponse(
                    ['message' => 'Allergens not found'],
                    Response::HTTP_NOT_FOUND
                );
            }
            $responseData = $this->serializer->serialize($allergen, 'json', [
                'groups' => ['allergen:read']
            ]);
            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la récupération de l\'allergène: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', methods: ['PUT'], name: 'edit')]
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
}
