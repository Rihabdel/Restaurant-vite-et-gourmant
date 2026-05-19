<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends AbstractController
{
    #[Route('/api/debug-db', name: 'debug_db')]
    public function debugDb(): JsonResponse
    {
        return new JsonResponse([
            'db' => $_ENV['DATABASE_URL'] ?? 'NOT SET'
        ]);
    }
}
