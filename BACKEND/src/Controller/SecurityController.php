<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use DateTimeImmutable;


#[Route('/api', name: 'app_api_')]
final class SecurityController extends AbstractController
{
    #[Route('/registration', name: 'registration', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        //  recuperer les données de la requete    
        $data = json_decode($request->getContent(), true);
        //  valider les données
        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(
                [
                    'error' => 'Missing data',
                    'message' => 'Email and password are required'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
        //  Désérialiser SANS le mot de passe
        unset($data['password']);
        $jsonWithoutPassword = json_encode($data);

        $user = $serializer->deserialize($jsonWithoutPassword, User::class, 'json');

        // HASHer le mot de passe et le setter
        $hashedPassword = $passwordHasher->hashPassword($user, $jsonWithoutPassword);
        $user->setPassword($hashedPassword);
        $user->setCreatedAt(new \DateTimeImmutable());

        // Persist et flush
        $em->persist($user);
        $em->flush();

        return new JsonResponse(
            [
                'message' => 'User created successfully',
                'user' => $user->getUserIdentifier(),
                'email' => $user->getEmail()
            ],
            Response::HTTP_CREATED
        );
    }
    // …

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(['message' => 'Missing credentials'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user'  => $user->getUserIdentifier(),
            'apiToken' => $user->getApiToken(),
            'roles' => $user->getRoles(),
        ]);
    }
}
