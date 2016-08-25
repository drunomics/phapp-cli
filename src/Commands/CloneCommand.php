<?php

namespace drunomics\Phapp\Commands;

use League\Container\ContainerAwareTrait;
use Robo\Common\IO;
use Robo\LoadAllTasks;


/**
 * Class CreateCommand.
 */
class CloneCommand {

  use ContainerAwareTrait;
  use LoadAllTasks;
  use IO;

  /**
   * Clones an app.
   *
   * @command clone
   */
  public function cloneApp() {

  }

}
