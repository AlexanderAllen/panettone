<?php

declare(strict_types=1);

namespace Drupal\Core;

// @phpcs:disable

/**
 * A result type that can either be OkT or ErrorT.
 *
 * A result type is a monadic type holding a returned value or an error code.
 * They provide an elegant way of handling errors, without resorting to
 * exception handling; when a function that may fail returns a result type,
 * the programmer is forced to consider success or failure paths, before
 * getting access to the expected result; this eliminates the possibility of
 * an erroneous programmer assumption.
 *
 * A monad is a structure that combines program fragments (functions) and wraps
 * their return values in a type with additional computation.
 *
 * @template OkT of boolean
 * @template IdentityValue
 */
final class Result {

    /**
     * @var OkT TRUE if the result is OkT or FALSE otherwise.
     */
    private $isOk;

    /**
     * @var IdentityValue
     */
    private mixed $value;

    /**
     * Create a new result.
     *
     * @param OkT $isOk
     * @param IdentityValue $value
     *   The value for the result.
     */
    public function __construct($isOk, $value,
    ) {
        $this->isOk = $isOk;
        $this->value = $value;
    }

  /**
   * Create a result that resolved to OkT.
   *
   * @template a
   * @param a $value
   * @return self<OkT, a>
   */
  public static function ok($value) {
    /** @var OkT $ok */
    $ok = true;
    return new self($ok, $value);
  }

  /**
   * Create a result that resolved to ErrorT.
   *
   * @param IdentityValue $value
   * @return self<OkT, IdentityValue>
   */
  public static function error($value) : self {
    /** @var OkT $ok */
    $ok = false;
    return new self($ok, $value);
  }

  /**
   * Check whether the result is OkT.
   *
   * @return OkT
   *   Whether the result is OkT.
   */
  public function isOk() {
    return $this->isOk;
  }

  /**
   * Check whether the result is ErrorT.
   *
   * @return OkT
   *   Whether the result is ErrorT.
   */
  public function isError() {
    return !$this->isOk;
  }

  /**
   * Get the value from the result.
   *
   * @return IdentityValue
   *   The value for the result, the type depends on whether the result is OkT
   *   or ErrorT.
   */
  public function getValue() {
    return $this->value;
  }

}

// Generic hint int is retained through instance creator.
$b = new Result(true, 3);
\PHPStan\dumpType($b);

// Generic is not retained through static, we've been here before.
// Class-level generics are lost through static, have to use local generics instead.
$a = Result::ok(5);
\PHPStan\dumpType($a);
