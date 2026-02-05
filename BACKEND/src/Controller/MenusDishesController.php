<?php

namespace App\Controller;

use App\Entity\MenusDishes;
use App\Entity\Menus;
use App\Entity\Dishes;
use App\Entity\DishAllergen;
use App\Repository\MenusDishesRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/api/menus-dishes', name: 'app_api_menus_dishes_')]
class MenusDishesController extends AbstractController
{
    public function __construct(

        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private MenusDishesRepository $menusDishesRepository
    ) {}

    // add dish to menu
    #[Route('/{id}', methods: ['POST'], name: 'add_dish_to_menu')]
    public function addDishToMenu(int $id, Request $request): JsonResponse

    {
        try {
            $menu = $this->entityManager->getRepository(Menus::class)->find($id);
            if (!$menu) {
                return new JsonResponse(
                    ['error' => 'Menu non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }
            //
            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(
                    ['error' => 'JSON invalide: ' . json_last_error_msg()],
                    Response::HTTP_BAD_REQUEST
                );
            }
            if (!isset($data['dish_id'])) {
                return new JsonResponse(
                    ['error' => 'le champ "dish_id" est obligatoire '],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $dishId = $data['dish_id'];
            $dish = $this->entityManager->getRepository(Dishes::class)->find($dishId);
            if (!$dish) {
                return new JsonResponse(
                    ['error' => 'Plat avec ID ' . $dishId . ' non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }
            //verifier si le plat est déjà dans le menu
            $existingMenuDish = $this->entityManager->getRepository(MenusDishes::class)->findOneBy([
                'menu' => $menu,
                'dish' => $dish
            ]);
            if ($existingMenuDish) {
                return new JsonResponse(
                    ['message' => 'Le plat est déjà dans le menu'],
                    Response::HTTP_OK
                );
            } else {
                $menuDish = new MenusDishes();
                $menuDish->setMenu($menu);
                $menuDish->setDish($dish);
                $menuDish->setDisplayOrder(count($menu->getMenusDishes()) + 1);
            }
            // valider le menuDish
            $errors = $this->validator->validate($menuDish);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }

                return new JsonResponse(
                    ['errors' => $errorMessages],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            // persist et flush
            $this->entityManager->persist($menuDish);
            $this->entityManager->flush();
            return new JsonResponse(
                [
                    'message' => 'Plat ajouté au menu avec succès',
                    'menu' => $menu->getTitle(),
                    'dish' => $dish->getName(),
                    'allergen_id' => $dish->getDishAllergens()->map(function ($allergen) {
                        return [
                            'id' => $allergen->getAllergen()->getId(),
                            'name' => $allergen->getAllergen()->getName()

                        ];
                    })->toArray(),
                ],
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Erreur serveur: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // afficher la liste des plats d'un menu
    #[Route('/{id}/list', methods: ['GET'], name: 'menu_dish_list')]
    public function getMenuDishList(int $id): JsonResponse
    {
        $menu = $this->entityManager->getRepository(Menus::class)->find($id);
        if (!$menu) {
            return new JsonResponse(
                ['message' => 'Menu non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
        $menusDishes = $this->entityManager->getRepository(MenusDishes::class)->findBy(
            ['menu' => $menu],
            ['display_order' => 'ASC']
        );
        $responseData = $this->serializer->serialize(
            $menusDishes,
            'json',
            ['groups' => ['menu_dish:read', 'menu_dish:list', 'dish_allergen:read']]
        );
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }
    // afficher menu avec plats et allergenes EN DETAIL
    #[Route('/{id}/detail', methods: ['GET'], name: 'detail')]
    public function detail(int $id): JsonResponse
    {
        $menu = $this->entityManager->getRepository(Menus::class)->find($id);
        if (!$menu) {
            return new JsonResponse(
                ['message' => 'Menu non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
        $responseData = $this->serializer->serialize(
            $menu,
            'json',
            ['groups' => ['menu:detail', 'dish:detail', 'menu_dish:detail', 'allergen:read']]
        );
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }
}
