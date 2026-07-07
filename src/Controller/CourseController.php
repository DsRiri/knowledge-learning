<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Purchase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CourseController
 * Displays course details and manages access
 */
class CourseController extends AbstractController
{
    /**
     * Show a specific course with its lessons
     * Check if the user has access to the course
     */
    #[Route('/course/{id}', name: 'app_course_show')]
    public function show(Course $course, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $hasAccess = false;

        if ($user) {
            // Check if user has purchased this course
            $purchase = $em->getRepository(Purchase::class)->findOneBy([
                'user' => $user,
                'purchasableType' => 'course',
                'purchasableId' => $course->getId(),
                'paymentStatus' => 'paid'
            ]);
            $hasAccess = $purchase !== null;
        }

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'hasAccess' => $hasAccess,
        ]);
    }
}