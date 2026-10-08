<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\Lookup;

/**
 * Cleans and checks the three fields of the lookup form.
 */
class LookupInputValidator
{
    public function normalize(string $incrementId, string $email, string $postcode): LookupInput
    {
        return new LookupInput(
            ltrim(trim($incrementId), '#'),
            strtolower(trim($email)),
            self::normalizePostcode($postcode)
        );
    }

    /**
     * @return string[] customer-facing errors; empty when valid
     */
    public function validate(LookupInput $input): array
    {
        $errors = [];
        if (!preg_match('/^[A-Za-z0-9_-]{1,32}$/', $input->incrementId)) {
            $errors[] = 'Enter the order number from your confirmation email.';
        }
        if (filter_var($input->email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Enter the email address used at checkout.';
        }
        if ($input->postcode === '' || strlen($input->postcode) > 20) {
            $errors[] = 'Enter the billing postcode.';
        }

        return $errors;
    }

    /**
     * "sw1a 1aa", "SW1A1AA" and " SW1A  1AA " all compare as "SW1A1AA".
     */
    public static function normalizePostcode(string $postcode): string
    {
        return strtoupper((string) preg_replace('/[\s-]+/', '', $postcode));
    }
}
