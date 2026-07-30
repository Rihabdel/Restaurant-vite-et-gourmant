<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use symfony\component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    function index(): Response
    {
        
        return $this->json([
            'message' => 'API Vite et Gourmand opérationnelle !',
            'status' => 'online'
        ]);
    }
}
