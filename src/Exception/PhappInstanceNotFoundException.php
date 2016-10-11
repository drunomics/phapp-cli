<?php

/**
 * @file
 * Contains drunomics\Phapp\Exception\PhappInstanceNotFound.
 */

namespace drunomics\Phapp\Exception;

use Exception;

/**
 * Exception thrown when no phapp instance was found.
 */
class PhappInstanceNotFoundException extends \Exception {

  /**
   * {@inheritdoc}
   */
  public function __construct($message = 'Unable to find a phapp.yml file in the current working directory or Git project.', $code = 0, \Exception $previous = NULL) {
    parent::__construct($message, $code, $previous);
  }

}
