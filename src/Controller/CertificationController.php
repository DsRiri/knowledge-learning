<?php

namespace App\Controller;

use App\Repository\CertificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CertificationController
 * Displays user's certifications
 */
class CertificationController extends AbstractController
{
    /**
     * Show all certifications obtained by the user
     */
    #[Route('/certifications', name: 'app_certifications')]
    public function index(CertificationRepository $certificationRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Get all certifications for the logged-in user
        $certifications = $certificationRepository->findBy(['user' => $user]);

        return $this->render('certification/index.html.twig', [
            'certifications' => $certifications,
        ]);
    }
}