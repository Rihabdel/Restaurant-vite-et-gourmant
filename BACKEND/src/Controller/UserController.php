<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Dom\Entity;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use symfony\component\Serializer\Normalizer\AbstractNormalizer;


#[Route('api/user', name: 'app_api_user_')]
final class UserController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private UserRepository $userRepository,


    ) {}

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    public function edit(Request $request, User $user): Response
    {
        $user = $this->userRepository->findOneById($user->getId());
        if ($user) {

            $user = $this->serializer->deserialize(
                $request->getContent(),
                User::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $user]
            );
            $user->setUpdatedAt(new DateTimeImmutable());
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            return new JsonResponse(
                null,
                JsonResponse::HTTP_NO_CONTENT
            );
        }
        return new JsonResponse(
            ['error' => 'User not found'],
            Response::HTTP_NOT_FOUND
        );
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->userRepository->findOneById($id);
        if ($user) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            return new JsonResponse(
                null,
                JsonResponse::HTTP_NO_CONTENT
            );
        }
        return new JsonResponse(
            ['error' => 'User not found'],
            Response::HTTP_NOT_FOUND
        );
    }
}
