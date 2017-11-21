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
   * @option bool $build Build before running an update if the app is in
   *   development mode.
   *
   * @command install
   */
  public function install(array $options = ['build' => TRUE]) {
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
        $this->io()->title('Installing...');
      });
    }
    $command = $this->phappManifest->getCommand('install');
    $collection->addTask(
      $this->taskExec($command)
    );
    return $collection;
  }

}
