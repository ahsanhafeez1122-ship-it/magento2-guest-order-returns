<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Test\Unit\Model\Lookup;

use AhsanHafeez\GuestReturns\Model\Lookup\LookupInputValidator;
use PHPUnit\Framework\TestCase;

final class LookupInputValidatorTest extends TestCase
{
    private LookupInputValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new LookupInputValidator();
    }

    public function testNormalizesWhatCustomersActuallyType(): void
    {
        $input = $this->validator->normalize(' #000001234 ', ' Jane.Doe@Example.co.uk ', ' sw1a  1aa ');

        self::assertSame('000001234', $input->incrementId);
        self::assertSame('jane.doe@example.co.uk', $input->email);
        self::assertSame('SW1A1AA', $input->postcode);
        self::assertSame([], $this->validator->validate($input));
    }

    public function testUkPostcodeFormatsCompareEqual(): void
    {
        self::assertSame(
            LookupInputValidator::normalizePostcode('EC1A 1BB'),
            LookupInputValidator::normalizePostcode('ec1a1bb')
        );
    }

    public function testReportsEachInvalidField(): void
    {
        $input = $this->validator->normalize('12 34; DROP', 'not-an-email', '   ');

        self::assertCount(3, $this->validator->validate($input));
    }
}
