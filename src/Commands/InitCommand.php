<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\PhappCommandBase;

/**
 * Inits phapp.yml for new apps.
 */
class InitCommand extends PhappCommandBase  {

  /**
   * {@inheritdoc}
   */
  protected $requiresPhappManifest = FALSE;

  /**
   * Initializes a new app.
   */
  public function initApp() {
    throw new \Exception('TODO');
  }
}
