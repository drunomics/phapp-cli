<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\PhappCommandBase;
use Robo\ResultData;

/**
 * Provides the status command.
 */
class StatusCommands extends PhappCommandBase  {

  /**
   * Checks for a working and installed application.
   *
   * If the application is not installed, the command exits with an error code
   * of 1.
   *
   * @command status
   */
  public function status() {
    $this->stopOnFail(FALSE);
    $result =
      $this->invokeManifestCommand('status')
      ->run();
    if ($result->getExitCode() != 0) {
      $return = new ResultData(ResultData::EXITCODE_ERROR, 'Application is not installed.');
    }
    else {
      $return = new ResultData(ResultData::EXITCODE_OK, 'Application is installed.');
    }
    $return->provideOutputdata();
    return $return;
  }

}
