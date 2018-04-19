<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\Exception\PhappEnvironmentUndefinedException;
use drunomics\Phapp\PhappCommandBase;

/**
 * Setup the project before building.
 */
class SetupCommands extends PhappCommandBase  {

  /**
   * Setups the phapp environment.
   *
   * The application is prepared for running in the given environment. This is
   * usually involves copying or linking some environment dependent config.
   *
   * @param string $env
   *   (optional) The phapp's environment to setup; e.g., live, test or local.
   *   If not specified, the environment variable PHAPP_ENV must be set
   *   accordingly.
   *
   * @command setup
   */
  public function setup($env = NULL) {
    if ($env) {
      putenv("PHAPP_ENV=$env");
    }
    if (!getenv('PHAPP_ENV')) {
      throw new PhappEnvironmentUndefinedException();
    }
    return $this->invokeManifestCommand('setup');
  }

}
