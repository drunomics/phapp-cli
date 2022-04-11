<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\Exception\LogicException;
use drunomics\Phapp\PhappCommandBase;
use SelfUpdate\SelfUpdateCommand;
use Symfony\Component\Console\Application;
use Robo\Robo;

/**
 * Supports self-updating phars.
 */
class SelfCommands extends PhappCommandBase {

  /**
   * {@inheritdoc}
   */
  protected $requiresPhappManifest = FALSE;

  /**
   * Updates the installed phar.
   *
   * @option bool $unstable Allows updating to unstable releases.
   *
   * @command self:update
   */
  public function selfUpdate($options = ['unstable' => FALSE]) {
    if (!\Phar::running()) {
      throw new LogicException("Unable to self-update if the application is not installed as phar.");
    }
    $cmd = new SelfUpdateCommand('Phapp CLI', Robo::application()->getVersion(), 'drunomics/phapp-cli');
    $app = new Application('Phapp CLI', Robo::application()->getVersion());
    $app->add($cmd);
    $app->run();
  }

}
