<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\PhappCommandBase;
use drunomics\Phapp\ServiceUtil\BuildCommandsTrait;

/**
 * Provides the install command.
 */
class InstallCommands extends PhappCommandBase  {

  use BuildCommandsTrait;

  /**
   * Installs the application.
   *
   * @option bool $build Build before running an update.
   *
   * @command install
   */
  public function install(array $options = ['build' => TRUE]) {
    $collection = $this->collectionBuilder();
    $collection->setProgressIndicator(NULL);
    if ($options['build']) {
      $collection->addCode(function() {
        $this->io()->title('Building...');
      });
      $collection->addTask(
        $this->getBuildCommands()->build(['clean' => FALSE])
      );
      $collection->addCode(function() {
        $this->io()->title('Installing...');
      });
    }
    $collection->addTask(
      $this->invokeManifestCommand('install')
    );
    return $collection;
  }

}
