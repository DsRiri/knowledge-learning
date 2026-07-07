<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase
{
    public function testPurchaseRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('POST', '/checkout/course/1', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);
        $this->assertResponseRedirects('/login');
    }

    public function testPurchaseCancelPage(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        $user = new User();
        $user->setEmail('test_cancel_' . time() . '@example.com');
        $user->setPassword(password_hash('Password123!', PASSWORD_BCRYPT));
        $user->setIsActive(true);
        $user->setIsVerified(true);
        
        $role = $entityManager->getRepository(\App\Entity\Role::class)->findOneBy(['name' => 'client']);
        if ($role) {
            $user->setRole($role);
        }
        $entityManager->persist($user);
        $entityManager->flush();

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form();
        $form['_username'] = $user->getEmail();
        $form['_password'] = 'Password123!';
        $client->submit($form);

        $client->request('GET', '/checkout/cancel');
        $this->assertResponseRedirects('/');
    }
}