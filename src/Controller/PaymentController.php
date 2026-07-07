<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Purchase;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * PaymentController
 * Handles Stripe payment processing for courses and lessons
 */
class PaymentController extends AbstractController
{
    /**
     * Create a checkout session for a course or lesson
     * Redirects user to Stripe Checkout page
     */
    #[Route('/checkout/{type}/{id}', name: 'app_checkout', methods: ['POST'])]
    public function checkout(
        string $type,
        int $id,
        Request $request,
        StripeService $stripeService,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        // Verify CSRF token
        $csrfToken = $request->headers->get('X-CSRF-TOKEN');

        if (!$csrfToken || !$csrfTokenManager->isTokenValid($csrfTokenManager->getToken('checkout'))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $user = $this->getUser();

        // User must be logged in
        if (!$user) {
            return $this->json(['error' => 'You must be logged in'], 401);
        }

        // User must have activated their account
        if (!$user->isActive()) {
            return $this->json(['error' => 'You must activate your account before purchasing'], 403);
        }

        // Find the purchasable item
        if ($type === 'course') {
            $purchasable = $em->getRepository(Course::class)->find($id);
        } elseif ($type === 'lesson') {
            $purchasable = $em->getRepository(Lesson::class)->find($id);
        } else {
            return $this->json(['error' => 'Invalid purchase type'], 400);
        }

        // Check if item exists
        if (!$purchasable) {
            return $this->json(['error' => 'Item not found'], 404);
        }

        // Check if user already owns this item
        $existingPurchase = $em->getRepository(Purchase::class)->findOneBy([
            'user' => $user,
            'purchasableType' => $type,
            'purchasableId' => $id,
            'paymentStatus' => 'paid'
        ]);

        if ($existingPurchase) {
            return $this->json(['error' => 'You already own this item'], 400);
        }

        // Generate success and cancel URLs
        $successUrl = $this->generateUrl('app_checkout_success', [
            'type' => $type,
            'id' => $id
        ], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        $cancelUrl = $this->generateUrl('app_checkout_cancel', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        // Create Stripe checkout session
        try {
            $session = $stripeService->createCheckoutSession(
                $purchasable,
                $successUrl,
                $cancelUrl,
                $user->getId()
            );
        } catch (\Exception $e) {
            return $this->json(['error' => 'Stripe error: ' . $e->getMessage()], 500);
        }

        // Create pending purchase record
        $purchase = new Purchase();
        $purchase->setUser($user);
        $purchase->setPurchasableType($type);
        $purchase->setPurchasableId($id);
        $purchase->setAmount($purchasable->getPrice());
        $purchase->setPaymentStatus('pending');
        $purchase->setStripePaymentId($session->id);

        $em->persist($purchase);
        $em->flush();

        return $this->json(['checkout_url' => $session->url]);
    }

    /**
     * Success page after payment confirmation
     * Updates purchase status from pending to paid
     */
    #[Route('/checkout/success/{type}/{id}', name: 'app_checkout_success')]
    public function success(string $type, int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Find pending purchase and mark as paid
        $purchase = $em->getRepository(Purchase::class)->findOneBy([
            'user' => $user,
            'purchasableType' => $type,
            'purchasableId' => $id,
            'paymentStatus' => 'pending'
        ]);

        if ($purchase) {
            $purchase->setPaymentStatus('paid');
            $em->flush();
        }

        $this->addFlash('success', 'Payment successful! You now have access to this content.');

        // Redirect to the purchased content
        if ($type === 'course') {
            return $this->redirectToRoute('app_course_show', ['id' => $id]);
        } else {
            return $this->redirectToRoute('app_lesson_show', ['id' => $id]);
        }
    }

    /**
     * Cancel page when payment is cancelled by user
     */
    #[Route('/checkout/cancel', name: 'app_checkout_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Payment was cancelled.');
        return $this->redirectToRoute('app_home');
    }
}