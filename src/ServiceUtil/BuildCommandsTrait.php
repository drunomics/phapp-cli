<?php

namespace drunomics\Phapp\ServiceUtil;

use drunomics\Phapp\Commands\BuildCommands;
use Robo\Robo;

/**
 * Allows setter injection and simple usage of the service.
 */
trait BuildCommandsTrait {

  /**
   * The build commands class object.
   *
   * @var \drunomics\Phapp\Commands\BuildCommands
   */
  protected $buildCommands;
  
  /**
   * Sets the command class object.
   *
   * @param \drunomics\Phapp\Commands\BuildCommands $buildCommands
   *   The build command class object.
   *
   * @return $this
   */
  public function setBuildCommands(BuildCommands $buildCommands) {
    $this->buildCommands = $buildCommands;
    return $this;
  }
  /**
   * Gets the build command object
   *
   * @return \drunomics\Phapp\Commands\BuildCommands
   *   The build command object
   */
  public function getBuildCommands() {
    if (empty($this->buildCommands)) {
      $this->buildCommands = Robo::getContainer()->get('\\' . BuildCommands::class . 'Commands');
    }
    return $this->buildCommands;
  }

  /**
   * Ensures the Build commands class has been initalized.
   *
   * @todo: Remove global initalization out of command init.
   *
   * @hook validate
   */
  public function initBuildCommands() {
    $this->getBuildCommands()->init();
  }

}
