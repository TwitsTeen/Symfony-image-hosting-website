<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainMenuController extends AbstractController
{
    #[Route('/', name: 'app_main_menu')]
    public function index(): Response
    {
        return $this->render('main_menu/index.html.twig', [
            'controller_name' => 'MainMenuController',
        ]);
    }
}
