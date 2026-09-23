<?php

use App\Support\ContactDetailDetector;

beforeEach(function (): void {
    $this->detector = new ContactDetailDetector;
});

it('spots a phone number however it is written', function (string $text): void {
    expect($this->detector->matches($text))->toBeTrue();
})->with([
    'Call me on 01023456789',
    'واتساب 010 2345 6789',
    'reach us at +20 100 234 5678',
    'رقمي ٠١٠٢٣٤٥٦٧٨٩',
    'phone: 010-2345-6789',
]);

it('spots an email address or messaging handle', function (string $text): void {
    expect($this->detector->matches($text))->toBeTrue();
})->with([
    'email sales@deltaroasters.eg for a better price',
    'add us on WhatsApp',
    'https://wa.me/201023456789',
]);

it('leaves an ordinary quote alone', function (string $text): void {
    expect($this->detector->matches($text))->toBeFalse();
})->with([
    '80% Arabica / 20% Robusta, medium-dark roast, 1kg valve bags.',
    'تسليم خلال 12 يومًا، الدفع 50% مقدم والباقي عند التسليم.',
    'Minimum order 100 kg. Free delivery within Cairo.',
    'Grade 42.5 cement, 800 bags, delivered in 3 batches.',
]);

it('reports which fields carry the contact details', function (): void {
    $offenders = $this->detector->offendingFields([
        'extra_specs' => 'Call 01023456789 for a discount',
        'payment_terms' => '50% upfront',
        'warranty_policy' => null,
    ]);

    expect($offenders)->toBe(['extra_specs']);
});
