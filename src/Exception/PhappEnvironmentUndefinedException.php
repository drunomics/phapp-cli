<?php

namespace drunomics\Phapp\Exception;

/**
 * Exception thrown when the phapp environment could not be determined.
 */
class PhappEnvironmentUndefinedException extends \Exception {

  /**
   * {@inheritdoc}
   */
  public function __construct($message = 'The phapp environment is undefined. Run phapp setup $ENVIRONMENT to initialize it.', $code = 0, \Exception $previous = NULL) {
    parent::__construct($message, $code, $previous);
  }

}
