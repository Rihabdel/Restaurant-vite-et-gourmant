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
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use DateTimeImmutable;
use OpenApi\Attributes as OA;


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
    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
        description: 'Login endpoint',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'username', type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful login',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', type: 'string'),
                        new OA\Property(property: 'token', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - missing credentials'
            )
        ]
    )]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json([
                'message' => 'missing credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }
        $token = $user->getApiToken();
        return $this->json([
            'user'  => $user->getUserIdentifier(),
            'token' => $token,
        ]);
    }
}
