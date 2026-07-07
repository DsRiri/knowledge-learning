<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegisterPageIsDisplayed(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testRegisterUserWithValidData(): void
{
    $client = static::createClient();
    $crawler = $client->request('GET', '/register');
    
    $form = $crawler->selectButton('S\'inscrire')->form();
    $form['registration_form[email]'] = 'test_' . time() . '@example.com';
    $form['registration_form[plainPassword]'] = 'Password123!';
    $form['registration_form[agreeTerms]'] = '1'; // <-- AJOUTE CETTE LIGNE (coche la case)
    
    $client->submit($form);
    
    // Should redirect to login page after registration
    $this->assertResponseRedirects('/login');
}
}