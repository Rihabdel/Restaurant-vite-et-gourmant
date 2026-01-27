<?php

namespace App\Controller;


use App\Entity\Menus;
use App\Repository\MenusRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/menus', name: 'app_api_menus_')]
final class MenusController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MenusRepository $menusRepository
    ) {}
    #[Route('/new', methods: 'POST', name: 'new')]
    public function new(EntityManagerInterface $entityManager): Response
    {
        $menus = new Menus();
        $menus->setTitle('Menu du jour');
        $menus->setDescription('Description du menu du jour');
        $menus->setPrice(19.99);
        $menus->setMinPeople(40);
        $menus->setStock(100);
        $menus->setCreatedAt(new \DateTimeImmutable());
        $menus->setConditions('commande avant 48h');
        $menus->setPicture('menu.jpg');
        $entityManager->persist($menus);
        $entityManager->flush();
        return $this->json(
            [
                'message' => 'Menus created successfully',
                'id' => $menus->getId(),
            ],
            Response::HTTP_CREATED
        );
    }
    #[Route('/show/{id}', methods: 'GET', name: 'show')]
    public function show(EntityManagerInterface $entityManager, int $id): Response
    {
        $menus = $entityManager->getRepository(Menus::class)->findOneById($id);
        if (!$menus) {
            throw $this->createNotFoundException(
                'No menus found for id ' . $id
            );
        }
        return $this->json($menus);
        [
            'id' => $menus->getId(),
            'title' => $menus->getTitle(),
            'description' => $menus->getDescription(),
            'price' => $menus->getPrice(),
            'min_people' => $menus->getMinPeople(),
            'stock' => $menus->getStock(),
            'createdAt' => $menus->getCreatedAt(),
            'conditions' => $menus->getConditions(),
            'picture' => $menus->getPicture(),
        ];
    }

    #[Route(methods: ['PUT'], name: 'edit')]
    public function edit(EntityManagerInterface $entityManager, int $id): Response
    {
        $menus = $entityManager->getRepository(Menus::class)->find($id);
        if (!$menus) {
            throw $this->createNotFoundException(
                'No menus found for id ' . $id
            );
        }
        $menus->setTitle('Updated title');
        $this->$entityManager->flush();
        return $this->redirectToRoute(
            'app_api_menus_show',
            ['id' => $menus->getId()]
        );
    }
    #[Route(methods: ['DELETE'], name: 'delete')]
    public function delete(EntityManagerInterface $entityManager, int $id): Response
    {

        $menus = $entityManager->getRepository(Menus::class)->find($id);
        if (!$menus) {
            throw $this->createNotFoundException(
                'No menus found for id ' . $id
            );
        }
        $entityManager->remove($menus);
        $entityManager->flush();
        return $this->json([
            'message' => 'Menus deleted successfully',
        ]);
    }
}
