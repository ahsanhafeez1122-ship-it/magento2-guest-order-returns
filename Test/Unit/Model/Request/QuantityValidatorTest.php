<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Test\Unit\Model\Request;

use AhsanHafeez\GuestReturns\Model\Request\QuantityValidator;
use PHPUnit\Framework\TestCase;

final class QuantityValidatorTest extends TestCase
{
    private QuantityValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new QuantityValidator();
    }

    public function testKeepsOnlyPositiveQuantities(): void
    {
        $result = $this->validator->validate(['11' => '2', '12' => '0', '13' => ''], [11 => 3.0, 12 => 1.0, 13 => 1.0]);

        self::assertTrue($result->isValid());
        self::assertSame([11 => 2.0], $result->lines);
    }

    public function testRejectsMoreThanReturnable(): void
    {
        $result = $this->validator->validate(['11' => '4'], [11 => 3.0]);

        self::assertFalse($result->isValid());
        self::assertSame([], $result->lines);
        self::assertStringContainsString('at most 3', $result->errors[0]);
    }

    public function testRejectsItemThatIsNotReturnable(): void
    {
        $result = $this->validator->validate(['99' => '1'], [11 => 3.0]);

        self::assertFalse($result->isValid());
    }

    public function testRejectsFractionsAndText(): void
    {
        self::assertFalse($this->validator->validate(['11' => '1.5'], [11 => 3.0])->isValid());
        self::assertFalse($this->validator->validate(['11' => 'two'], [11 => 3.0])->isValid());
        self::assertFalse($this->validator->validate(['abc' => '1'], [11 => 3.0])->isValid());
    }

    public function testRequiresAtLeastOneItem(): void
    {
        $result = $this->validator->validate(['11' => '0'], [11 => 3.0]);

        self::assertSame(['Choose at least one item to return.'], $result->errors);
    }

    public function testDuplicateErrorsAreShownOnce(): void
    {
        $result = $this->validator->validate(['11' => '1.5', '12' => '0.5'], [11 => 3.0, 12 => 3.0]);

        self::assertCount(1, $result->errors);
    }
}
