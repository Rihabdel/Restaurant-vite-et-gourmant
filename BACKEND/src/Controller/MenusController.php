<?php

namespace App\Controller;


use App\Entity\Menus;
use App\Enum\Theme;
use App\Enum\Diet;
use App\Repository\MenusRepository;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;



#[Route('api/menu', name: 'app_api_menus_')]
final class MenusController extends AbstractController
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
        $menu = new Menus();
        //decoder le json
        $data = json_decode($request->getContent(), true);
        if (!isset($data['theme'])) {
            return new JsonResponse(
                ['erreurs' => 'Le thème est obligatoire'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        $themeMenu = Theme::tryFrom($data['theme']);

        if (!$themeMenu) {
            return new JsonResponse(
                ['erreurs' => 'Thème invalide'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        if (!isset($data['diet'])) {
            return new JsonResponse(
                ['erreurs' => 'Le régime est obligatoire'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        $dietMenu = Diet::tryFrom($data['diet']);

        if (!$dietMenu) {
            return new JsonResponse(
                ['erreurs' => 'Régime invalide'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        //supprimer les champs enum du tableau
        unset($data['theme']);
        unset($data['diet']);
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


    #[Route('', name: 'list', methods: ['GET'])]
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

                $responseData = $this->serializer->serialize($menu, 'json', [
                    'groups' => 'menu:list'
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
    public function show(int $id, MenusRepository $menusRepository): Response

    {
        $menus = $menusRepository->find($id);
        if (!$menus) {
            return new JsonResponse(
                ['message' => 'Aucun menu non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
        $responseData = $this->serializer->serialize($menus, 'json');
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['PUT'], name: 'edit')]
    public function edit(EntityManagerInterface $entityManager, int $id): Response
    {
        $menus = $entityManager->getRepository(Menus::class)->find($id);
        if (!$menus) {
            return new JsonResponse(
                ['message' => 'Menus not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        $menus->setUpdatedAt(new DateTimeImmutable());
        $menus = $this->serializer->deserialize(
            file_get_contents('php://input'),
            Menus::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $menus]
        );

        $entityManager->persist($menus);
        $entityManager->flush();
        $responseData = $this->serializer->serialize($menus, 'json');
        $location = $this->generateUrl(
            'app_api_menus_show',
            ['id' => $menus->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return new JsonResponse($responseData, Response::HTTP_OK, ['Location' => $location], true);
    }
    #[Route('/{id}', methods: ['DELETE'], name: 'delete')]
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
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }
}
