<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\Exception\PhappEnvironmentUndefinedException;
use drunomics\Phapp\PhappCommandBase;
use drunomics\Phapp\PhappManifest;

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
      putenv("PHAPP_ENV={$env}");
    }
    if (!getenv('PHAPP_ENV')) {
      throw new PhappEnvironmentUndefinedException();
    }
    // Remove the environment command by replacing the manifest.
    // The setup command must work without a prepared environment; i.e. the
    // environment command is not yet available.
    $content = $this->phappManifest->getContent();
    $content['commands']['environment'] = "export PHAPP_ENV={$env}";
    $this->phappManifest = new PhappManifest($content, $this->phappManifest->getFile());
    return $this->invokeManifestCommand('setup');
  }

}
