<?php

namespace Tests\Property;

use Eris\Generator;

/**
 * Example Property Test to verify setup
 */
class ExamplePropertyTest extends PropertyTestCase
{
    /**
     * Example: String concatenation length property
     * For any two strings, the length of their concatenation equals
     * the sum of their individual lengths.
     */
    public function test_string_concatenation_length(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string()
        )->then(function (string $a, string $b) {
            $this->assertEquals(
                strlen($a) + strlen($b),
                strlen($a . $b)
            );
        });
    }

    /**
     * Example: Array reverse is involutory
     * For any array, reversing it twice returns the original array.
     */
    public function test_array_reverse_involutory(): void
    {
        $this->forAll(
            Generator\seq(Generator\int())
        )->then(function (array $arr) {
            $this->assertEquals(
                $arr,
                array_reverse(array_reverse($arr))
            );
        });
    }
}
