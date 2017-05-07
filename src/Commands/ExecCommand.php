<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\PhappCommandBase;

/**
 * Class ExecCommand.
 */
class ExecCommand extends PhappCommandBase {

  /**
   * Executes commands based on the environment.
   *
   * @param string $exec_command The command to execute.
   * @param string[] $arguments The command arguments.
   *
   * @command exec
   */
  public function execCommand($exec_command, array $arguments) {
    $currentpath = realpath(getcwd());
    if ($strpos = strpos($currentpath, "vcs/web")) {
      $projectpath = substr($currentpath, 0, $strpos);
      $project = basename($projectpath);
      // Detect vagrant environment
      if (file_exists($projectpath . '.vagrant')) {
        $exec_command = "docker exec " . $project . " " . $exec_command;
      }
      else {
        // Use the wrapper script for drush.
        if ($exec_command == "drush") {
          $exec_command = $projectpath . "/vcs/web/drush.wrapper";
        }
      }
      $this->_exec($exec_command . ' ' . implode(' ', $arguments));
    }
     else {
      print("You need to be in the web directory to execute this command.\n");
    }
  }
}
