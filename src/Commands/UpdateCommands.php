<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\PhappCommandBase;
use drunomics\Phapp\ServiceUtil\BuildCommandsTrait;

/**
 * Updates the app.
 */
class UpdateCommands extends PhappCommandBase  {

  use BuildCommandsTrait;

  /**
   * Updates the app.
   *
   * @option bool $build Build before running an update if the app is in
   *   development mode.
   *
   * @command update
   */
  public function update(array $options = ['build' => TRUE]) {
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
        $this->io()->title('Updating...');
      });
    }
    $command = $this->phappManifest->getCommand('update');
    $collection->addTask(
      $this->taskExec($command)
    );
    return $collection;
  }

}
