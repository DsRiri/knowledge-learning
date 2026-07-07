<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Course;
use App\Entity\Purchase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * AdminController
 * Handles admin dashboard and user management
 */
#[Route('/admin')]
class AdminController extends AbstractController
{
    /**
     * Admin dashboard with statistics
     */
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        // Check if user is admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Get statistics
        $totalUsers = $em->getRepository(User::class)->count([]);
        $totalCourses = $em->getRepository(Course::class)->count([]);
        $totalPurchases = $em->getRepository(Purchase::class)->count([]);

        // Get recent users
        $recentUsers = $em->getRepository(User::class)->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalCourses' => $totalCourses,
            'totalPurchases' => $totalPurchases,
            'recentUsers' => $recentUsers,
        ]);
    }

    /**
     * List all users
     */
    #[Route('/users', name: 'app_admin_users')]
    public function users(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $em->getRepository(User::class)->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }
}