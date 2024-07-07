<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class RedirectController extends AbstractController
{
    #[Route('/redirect', name: 'app_redirect')]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('product_list');
    }
}
