<?php

namespace drunomics\Phapp\ServiceUtil;

use drunomics\Phapp\Commands\GitCommands;
use Robo\Robo;

/**
 * Allows setter injection and simple usage of the service.
 */
trait GitCommandsTrait {

  /**
   * The git commands class object.
   *
   * @var \drunomics\Phapp\Commands\GitCommands
   */
  protected $gitCommands;
  
  /**
   * Sets the command class object.
   *
   * @param \drunomics\Phapp\Commands\GitCommands $gitCommands
   *   The git command class object.
   *
   * @return $this
   */
  public function setGitCommands(GitCommands $gitCommands) {
    $this->gitCommands = $gitCommands;
    return $this;
  }
  /**
   * Gets the git command object
   *
   * @return \drunomics\Phapp\Commands\GitCommands
   *   The git command object
   */
  public function getGitCommands() {
    if (empty($this->gitCommands)) {
      $this->gitCommands = Robo::getContainer()->get(GitCommands::class);
    }
    return $this->gitCommands;
  }

}
