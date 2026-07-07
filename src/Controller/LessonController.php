<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Entity\Purchase;
use App\Entity\UserLesson;
use App\Entity\Certification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * LessonController
 * Displays lesson content and handles lesson validation
 */
class LessonController extends AbstractController
{
    /**
     * Show a specific lesson
     * Check if user has access and if lesson is validated
     */
    #[Route('/lesson/{id}', name: 'app_lesson_show')]
    public function show(Lesson $lesson, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $hasAccess = false;
        $isValidated = false;

        if ($user) {
            // Check if user purchased this lesson
            $purchase = $em->getRepository(Purchase::class)->findOneBy([
                'user' => $user,
                'purchasableType' => 'lesson',
                'purchasableId' => $lesson->getId(),
                'paymentStatus' => 'paid'
            ]);

            // Check if user purchased the whole course
            $coursePurchase = $em->getRepository(Purchase::class)->findOneBy([
                'user' => $user,
                'purchasableType' => 'course',
                'purchasableId' => $lesson->getCourse()->getId(),
                'paymentStatus' => 'paid'
            ]);

            $hasAccess = ($purchase !== null || $coursePurchase !== null);

            // Check if lesson is already validated
            $userLesson = $em->getRepository(UserLesson::class)->findOneBy([
                'user' => $user,
                'lesson' => $lesson
            ]);
            $isValidated = $userLesson && $userLesson->isValidated();
        }

        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
            'hasAccess' => $hasAccess,
            'isValidated' => $isValidated,
        ]);
    }

    /**
     * Validate a lesson (AJAX endpoint)
     * Automatically grants certification if all lessons in course are validated
     */
    #[Route('/lesson/{id}/validate', name: 'app_lesson_validate', methods: ['POST'])]
    public function validateLesson(Lesson $lesson, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'You must be logged in'], 401);
        }

        // Check if user has access to this lesson
        $hasAccess = $em->getRepository(Purchase::class)->findOneBy([
            'user' => $user,
            'purchasableType' => 'lesson',
            'purchasableId' => $lesson->getId(),
            'paymentStatus' => 'paid'
        ]) || $em->getRepository(Purchase::class)->findOneBy([
            'user' => $user,
            'purchasableType' => 'course',
            'purchasableId' => $lesson->getCourse()->getId(),
            'paymentStatus' => 'paid'
        ]);

        if (!$hasAccess) {
            return $this->json(['error' => 'You do not have access to this lesson'], 403);
        }

        // Validate the lesson
        $userLesson = $em->getRepository(UserLesson::class)->findOneBy([
            'user' => $user,
            'lesson' => $lesson
        ]);

        if (!$userLesson) {
            $userLesson = new UserLesson();
            $userLesson->setUser($user);
            $userLesson->setLesson($lesson);
        }

        $userLesson->setIsValidated(true);
        $userLesson->setValidatedAt(new \DateTimeImmutable());

        $em->persist($userLesson);
        $em->flush();

        // Check if all lessons in course are validated → automatic certification
        $this->checkAndAwardCertification($user, $lesson->getCourse(), $em);

        return $this->json(['success' => 'Lesson validated!']);
    }

    /**
     * Check if all lessons in a course are validated and award certification
     */
    private function checkAndAwardCertification($user, $course, EntityManagerInterface $em): void
    {
        // Get all lessons in this course
        $lessons = $em->getRepository(Lesson::class)->findBy(['course' => $course]);

        // Check if user has validated all lessons
        $allValidated = true;
        foreach ($lessons as $lesson) {
            $userLesson = $em->getRepository(UserLesson::class)->findOneBy([
                'user' => $user,
                'lesson' => $lesson,
                'isValidated' => true
            ]);

            if (!$userLesson) {
                $allValidated = false;
                break;
            }
        }

        // If all lessons are validated, award certification
        if ($allValidated) {
            // Check if certification already exists
            $existingCert = $em->getRepository(Certification::class)->findOneBy([
                'user' => $user,
                'course' => $course
            ]);

            if (!$existingCert) {
                $certification = new Certification();
                $certification->setUser($user);
                $certification->setCourse($course);
                $certification->setObtainedAt(new \DateTimeImmutable());
                $em->persist($certification);
                $em->flush();
            }
        }
    }
}