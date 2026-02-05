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

#[Route('api/dishes', name: 'app_api_dishes_')]
final class DishesController extends AbstractController
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
            if (!isset($data['name']) || !isset($data['description']) || !isset($data['price'])) {
                return new JsonResponse(
                    ['error' => 'Les champs nom du plat, description et prix sont obligatoires'],
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
            if (isset($data['allergens'])) {
                if (is_array($data['allergens'])) {

                    $allergenIds = $data['allergens'];
                } else {
                    return new JsonResponse(
                        ['error' => 'Le champ allergènes doit être un tableau d\'IDs'],
                        Response::HTTP_UNPROCESSABLE_ENTITY
                    );
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
            $dish->setUpdatedAt(new DateTimeImmutable());

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
                'groups' => ['dish:read']
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



    #[Route('/{id}', methods: ['GET'], name: 'show')]
    public function show(int $id, DishesRepository $dishesRepository): Response

    {
        $dish = $dishesRepository->find($id);
        if (!$dish) {
            return new JsonResponse(
                ['message' => 'Dishes not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        $responseData = $this->serializer->serialize($dish, 'json');
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['PUT'], name: 'edit')]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        $dish = $em->getRepository(Dishes::class)->find($id);

        if (!$dish) {
            return $this->json(['error' => 'Dish not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        // Mettre à jour l'entité
        if (isset($data['name'])) {
            $dish->setName($data['name']);
        }

        // Validation
        $errors = $validator->validate($dish);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'error' => 'Validation failed',
                'errors' => $errorMessages
            ], 400);
        }

        $em->flush();

        return $this->json($dish, 200, [], ['groups' => ['dish:read']]);
    }

    #[Route('/{id}', methods: ['DELETE'], name: 'delete')]
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
