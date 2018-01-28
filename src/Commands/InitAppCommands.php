<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\PhappCommandBase;
use drunomics\Phapp\ServiceUtil\BuildCommandsTrait;

/**
 * Inits the app.
 */
class InitAppCommands extends PhappCommandBase  {

  use BuildCommandsTrait;

  /**
   * Initializes the app.
   *
   * Initializes the app as defined by the app; i.e., either per installing from
   * scratch or importing a database dump.
   *
   * @option bool $build Build before running an update if the app is in
   *   development mode.
   *
   * @command init
   */
  public function initApp(array $options = ['build' => TRUE]) {
    $collection = $this->collectionBuilder();
    $collection->setProgressIndicator(NULL);
    if (getenv('PHAPP_ENV_MODE') == 'development' && $options['build']) {
      $collection->addCode(function() {
        $this->io()->title('Building...');
      });
      $collection->addTask(
        $this->getBuildCommands()->build(['clean' => FALSE])
      );
      $collection->addCode(function() {
        $this->io()->title('Initializing...');
      });
    }
    $collection->addTask(
      $this->invokeManifestCommand('init')
    );
    return $collection;
  }
}
