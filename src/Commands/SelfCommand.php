<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\Exception\LogicException;
use Humbug\SelfUpdate\Updater;
use Robo\Robo;
use Robo\Tasks;

/**
 * Class SelfCommand.
 */
class SelfCommand extends Tasks {

  /**
   * Updates the installed phar.
   *
   * @command self:update
   */
  public function selfUpdate() {
    $updater = new Updater(NULL, FALSE);
    $updater->setStrategy(Updater::STRATEGY_GITHUB);
    $updater->getStrategy()->setPackageName('drunomics/phapp-cli');
    $updater->getStrategy()->setPharName('phapp.phar');
    $updater->getStrategy()->setCurrentLocalVersion(Robo::application()->getVersion());

    if (!\Phar::running()) {
      throw new LogicException("Unable to self-update if the application is not installed as phar.");
    }

    if ($updater->update()) {
      $this->say("Application updated to version " . $updater->getNewVersion());
      // Phar cannot load more classes after the update has occurred. So to
      // avoid errors from classes loaded after this (e.g.
      // ConsoleTerminateEvent), we exit directly now.
      exit(0);
    }
    else {
      $version = $updater->getNewVersion();
      $this->say("Most recent version $version installed.");
    }
  }

  /**
   * Rolls back to the previous version after a self-update.
   *
   * @command self:rollback
   */
  public function rollback() {
    $updater = new Updater();
    if ($result = $updater->rollback()) {
      // Phar cannot load more classes after the update has occurred. So to
      // avoid errors from classes loaded after this (e.g.
      // ConsoleTerminateEvent), we exit directly now.
      $this->say("Application rolled back to version " . $updater->getOldVersion());
      exit(0);
    }
  }
}
