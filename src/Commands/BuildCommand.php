<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\Phapp;
use Robo\Tasks;

/**
 * Class BuildCommand.
 */
class BuildCommand extends Tasks {

  /**
   * Clones an app.
   *
   * @command build
   */
  public function execute() {

    $phapp = Phapp::getInstance();

  }

}
