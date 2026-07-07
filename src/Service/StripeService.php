<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeService
{
    private string $stripeSecretKey;

    public function __construct(string $stripeSecretKey)
    {
        $this->stripeSecretKey = $stripeSecretKey;
        Stripe::setApiKey($this->stripeSecretKey);
    }

    public function createCheckoutSession(object $purchasable, string $successUrl, string $cancelUrl, int $userId): Session
    {
        $type = strtolower((new \ReflectionClass($purchasable))->getShortName());

        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $purchasable->getTitle(),
                    ],
                    'unit_amount' => intval($purchasable->getPrice() * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'purchasable_type' => $type,
                'purchasable_id' => $purchasable->getId(),
                'user_id' => $userId,
            ],
        ]);
    }
}