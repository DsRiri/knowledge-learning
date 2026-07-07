<?php

namespace App\Security;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * EmailVerifier
 * Handles email verification for user registration
 */
class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Send an email confirmation to the user
     */
    public function sendEmailConfirmation(string $routeName, UserInterface $user, TemplatedEmail $email): void
    {
        // Get the token directly from the user
        $token = $user->getActivationToken();

        // Build the URL manually with the token
        $signedUrl = $this->urlGenerator->generate($routeName, [
            'token' => $token
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email->context(['signedUrl' => $signedUrl]);

        $this->mailer->send($email);
    }

    /**
     * Handle email confirmation request
     */
    public function handleEmailConfirmation(Request $request, UserInterface $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmation(
            $request->getUri(),
            $user->getId(),
            $user->getEmail()
        );

        $user->setIsVerified(true);
        $user->setIsActive(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}