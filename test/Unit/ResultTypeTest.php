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
 * @template OkT of bool
 * @template IdentityValue
 */
final class Result {

    /**
     * @var OkT TRUE if the result is OkT or FALSE otherwise.
     */
    private bool $isOk;

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
    private function __construct($isOk, $value,
    ) {
        $this->isOk = $isOk;
        $this->value = $value;
    }

  /**
   * Create a result that resolved to OkT.
   *
   * @param IdentityValue $value
   * @return self<OkT, IdentityValue>
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

function accepts_int(int $foo) : void {}
function accepts_string(string $bar) : void {}

/**
 * @param \Drupal\Core\Result<bool, mixed> $result
 */
function accepts_result(\Drupal\Core\Result $result) : void {
  if ($result->isOk()) {
    accepts_int($result->getValue());
  }
  else {
    accepts_string($result->getValue());
  }
}

/**
 * @return \Drupal\Core\Result<bool, mixed>
 * @phpstan-impure
 */
function returns_result() : Result {
    return rand(0, 1) === 0
        ? \Drupal\Core\Result::ok(1)
        : \Drupal\Core\Result::error("foo");
}

accepts_result(returns_result());
accepts_result(returns_result());

accepts_int(\Drupal\Core\Result::ok(5)->getValue());
accepts_int(\Drupal\Core\Result::error(5)->getValue());
accepts_string(\Drupal\Core\Result::ok("five")->getValue());
accepts_string(\Drupal\Core\Result::error("five")->getValue());
