<?php

namespace drunomics\Phapp\Commands;

use League\Container\ContainerAwareTrait;
use Robo\Common\IO;
use Robo\LoadAllTasks;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Class ExecCommand.
 */
class ExecCommand {

  use ContainerAwareTrait;
  use LoadAllTasks;
  use IO;


  /**
   * Executes commands based on the environment.
   *
   * @command exec
   */
  public function execCommand($name) {
    $currentpath = realpath(getcwd());
    if ($strpos = strpos($currentpath, "vcs/web")) {
      $projectpath = substr($currentpath, 0, $strpos);
      $project = basename($projectpath);
      // Detect vagrant environment
      if (file_exists($projectpath . '.vagrant')) {
        $command = "docker exec " . $project . " " . $name;
      }
      else {
        // Use the wrapper script for drush.
        if ($name == "drush") {
          $command = $projectpath . "/vcs/web/drush.wrapper";
        }
        else {
          $command = $name;
        }
      }
      $this->_exec($command);
    }
     else {
      print("You need to be in the web directory to execute this command.\n");
    }
  }
}
