<?php

/**
 * @file
 * Contains drunomics\Phapp\Task\Exec.
 */

namespace drunomics\Phapp\Task;

/**
 * Class Exec.
 */
class Exec extends \Robo\Task\Base\Exec {

  protected function execute($process, $output_callback = NULL) {
    $process->inheritEnvironmentVariables(TRUE);
    return parent::execute($process, $output_callback);
  }

}
