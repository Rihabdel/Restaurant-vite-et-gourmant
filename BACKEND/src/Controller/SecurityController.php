<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use DateTimeImmutable;
use OpenApi\Attributes as OA;


#[Route('/api', name: 'app_api_')]
class SecurityController extends AbstractController
{
    public function __construct(private EntityManagerInterface $manager, private SerializerInterface $serializer) {}

    #[Route('/registration', name: 'registration', methods: 'POST')]
    #[OA\Post(
        tags: ['Authentication'],
        description: 'Registration endpoint',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                    new OA\Property(property: 'firstName', type: 'string'),
                    new OA\Property(property: 'lastName', type: 'string'),
                    new OA\Property(property: 'phone', type: 'int'),
                    new OA\Property(property: 'address', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                        new OA\Property(property: 'firstName', type: 'string', example: 'John'),
                        new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
                        new OA\Property(property: 'phone', type: 'int', example: 1234567890),
                        new OA\Property(property: 'address', type: 'string', example: '123 Main St'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Bad request - invalid data provided')
        ]
    )]

    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'JSON invalide'], 400);
        }

        if (empty($data['email']) || empty($data['password'])) {
            return new JsonResponse(['error' => 'Champs obligatoires manquants'], 400);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPhone($data['phone'] ?? null);
        $user->setAddress($data['address'] ?? null);
        $user->setLastName($data['lastName'] ?? null);
        $user->setFirstName($data['firstName'] ?? null);
        $user->setPassword(
            $passwordHasher->hashPassword($user, $data['password'])
        );
        // gerer les roles
        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->setRoles($data['roles']);
        } else {
            $user->setRoles(['ROLE_USER']);
        }
        $user->setCreatedAt(new DateTimeImmutable());
        $this->manager->persist($user);
        $this->manager->flush();

        return new JsonResponse([
            'user' => $user->getUserIdentifier(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'phone' => $user->getPhone(),
            'address' => $user->getAddress(),
            'roles' => $user->getRoles()
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
        tags: ['Authentication'],
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
                        new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                        new OA\Property(property: 'token', type: 'string', example: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Unauthorized - invalid credentials', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'mandatory credentials are missing'),]))
        ]
    )]

    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse(['message' => 'Identifiants invalides'], 401);
        }

        return new JsonResponse([
            'user' => $user->getUserIdentifier(),
            'apiToken' => $user->getApiToken(),
            'roles' => $user->getRoles()
        ]);
    }
    #[Route('/user', name: 'user_info', methods: ['GET'])]
    #[OA\Get(
        tags: ['Authentication'],
        summary: 'compte utilisateur',
        description: 'affiche les informations de l\'utilisateur connecté',
        responses: [
            new OA\Response(
                response: 200,
                description: 'User information retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_USER']),
                    ]

                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized - invalid token', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'non'),]))
        ]
    )]

    public function userInfo(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse(['message' => 'non connecté'], 401);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
            'token' => $user->getApiToken(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'address' => $user->getAddress(),
            'phone' => $user->getPhone(),
            'createdAt' => $user->getCreatedAt(),

        ]);
    }
    #[Route('/logout', name: 'logout', methods: ['POST'])]
    #[OA\Post(
        tags: ['Authentication'],
        summary: 'Logout endpoint',
        description: 'Logs out the user by clearing the authentication cookie',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful logout',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Déconnexion réussie'),
                    ]
                )
            )
        ]
    )]
    public function logout(): JsonResponse
    {
        $response = new JsonResponse(['message' => 'Déconnexion réussie']);
        $response->headers->clearCookie('token');
        return $response;
    }
    #[Route('/user', name: 'update', methods: ['PUT'])]
    #[OA\Put(
        tags: ['Authentication'],
        summary: 'Edit user profile',
        description: 'Allows the authenticated user to edit their profile information',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', example: 'John'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
                    new OA\Property(property: 'phone', type: 'int', example: 1234567890),
                    new OA\Property(property: 'address', type: 'string', example: '123 Main St'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 204,
                description: 'Profile updated successfully'
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - invalid data provided'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - user not authenticated'
            )
        ]
    )]
    //modification du profil de l'utilisateur connecté
    public function edit(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $this->serializer->deserialize(
            $request->getContent(),
            User::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $this->getUser()],
        );

        $user->setUpdatedAt(new DateTimeImmutable());

        if (isset($request->toArray()['password'])) {
            $user->setPasswordHash($passwordHasher->hashPassword($user, $request->toArray()['password']));
        }
        $this->manager->persist($user);
        $this->manager->flush();

        return $this->json($user, Response::HTTP_OK, [], ['groups' => ['user:read']]);
    }

    #[Route('/admin/employees', name: 'admin_users', methods: ['GET'])]
    public function getUsers(UserRepository $userRepository): JsonResponse
    {
        try {

            $users = $userRepository->findEmployees();
            return $this->json($users, 200, [], ['groups' => ['user:read']]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }
    //suupprimer un utilisateur
    #[Route('/admin/employees/{id}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(int $id, UserRepository $userRepository): JsonResponse
    {
        try {
            $user = $userRepository->find($id);
            if (!$user) {
                return new JsonResponse(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
            }
            $this->manager->remove($user);
            $this->manager->flush();
            return new JsonResponse(['message' => 'Utilisateur supprimé avec succès'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }
}
