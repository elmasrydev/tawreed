<?php

use App\Support\PhoneNumber;

it('normalises every written form of an egyptian mobile to one stored value', function (string $input): void {
    expect(PhoneNumber::normalize($input))->toBe('01023456789');
})->with([
    '01023456789',
    '010 2345 6789',
    '+20 10 2345 6789',
    '002010 2345 6789',
    '2010-2345-6789',
]);

it('accepts the four egyptian mobile prefixes and rejects anything else', function (): void {
    expect(PhoneNumber::isValidEgyptianMobile('01023456789'))->toBeTrue()
        ->and(PhoneNumber::isValidEgyptianMobile('01123456789'))->toBeTrue()
        ->and(PhoneNumber::isValidEgyptianMobile('01223456789'))->toBeTrue()
        ->and(PhoneNumber::isValidEgyptianMobile('01523456789'))->toBeTrue()
        ->and(PhoneNumber::isValidEgyptianMobile('01323456789'))->toBeFalse()
        ->and(PhoneNumber::isValidEgyptianMobile('0102345678'))->toBeFalse()
        ->and(PhoneNumber::isValidEgyptianMobile('021234567'))->toBeFalse();
});
