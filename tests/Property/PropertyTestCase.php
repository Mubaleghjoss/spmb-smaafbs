<?php

namespace Tests\Property;

use Eris\TestTrait;
use Tests\TestCase;

/**
 * Base class for Property-Based Tests
 * 
 * Property-based tests verify universal properties that should hold
 * across all valid inputs. Each test runs minimum 100 iterations.
 */
abstract class PropertyTestCase extends TestCase
{
    use TestTrait;

    /**
     * Minimum iterations for property tests
     */
    protected int $minIterations = 100;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure Eris for minimum iterations
        $this->limitTo($this->minIterations);
    }
}
