<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

/**
 * RegistrationController
 * Handles user registration and email verification
 */
class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Assign default role 'client' to new user (required by project specs)
            $roleClient = $entityManager->getRepository(Role::class)->findOneBy(['name' => 'client']);
            if ($roleClient) {
                $user->setRole($roleClient);
            }

            // User starts as inactive until email verification
            $user->setIsActive(false);
            $user->setIsVerified(false);

            // Generate activation token
            $token = bin2hex(random_bytes(32));
            $user->setActivationToken($token);

            $entityManager->persist($user);
            $entityManager->flush();

            // Generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('noreply@knowledge-learning.com', 'Knowledge Learning Team'))
                    ->to((string) $user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            $this->addFlash('success', 'Please check your email to activate your account.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Get token from URL query parameter
        $token = $request->query->get('token');

        if (!$token) {
            $this->addFlash('error', 'No verification token found.');
            return $this->redirectToRoute('app_register');
        }

        // Find user by token
        $user = $entityManager->getRepository(User::class)->findOneBy(['activationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Invalid verification token. Please try registering again.');
            return $this->redirectToRoute('app_register');
        }

        // Activate the user
        $user->setIsVerified(true);
        $user->setIsActive(true);
        $user->setActivationToken(null);
        $entityManager->flush();

        $this->addFlash('success', 'Your email has been verified! You can now login.');
        return $this->redirectToRoute('app_login');
    }
}