<?php

namespace drunomics\Phapp\Commands;

use League\Container\ContainerAwareTrait;
use Robo\Common\IO;
use Robo\LoadAllTasks;

/**
 * Class CreateCommand.
 */
class InitCommand {

  use ContainerAwareTrait;
  use LoadAllTasks;
  use IO;

  /**
   * Initializes a new app.
   */
  public function initApp() {

  }
}
