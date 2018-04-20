<?php

/**
 * @file
 * Contains drunomics\Phapp\Task\Exec.
 */

namespace drunomics\Phapp\Task;
use drunomics\Phapp\Exception\PhappEnvironmentUndefinedException;
use drunomics\Phapp\PhappManifest;
use Robo\Robo;
use Symfony\Component\Process\Process;

/**
 * Class Exec.
 */
class Exec extends \Robo\Task\Base\Exec {

  /**
   * The phapp manifest used for preparing the environment.
   *
   * @var PhappManifest
   */
  protected $manifest = NULL;

  /**
   * Adds the phapp environment to the command environment.
   *
   * @return $this
   */
  public function addPhappEnvironment(PhappManifest $manifest) {
    $this->manifest = $manifest;
    // @see ::getCommand().
    return $this;
  }

  /**
   * Ensures the PHAPP_ENV variable is set; i.e. setup has been done.
   *
   * @throws \drunomics\Phapp\Exception\PhappEnvironmentUndefinedException
   *   Thrown when the environment is undefined.
   */
  protected function ensureValidPhappEnvironment() {
    // Ensure the PHAPP_ENV variable will be set after running the environment
    // command.
    $command = ' [ ! -z "$PHAPP_ENV" ]';
    if ($env_command = $this->manifest->getCommand('environment')) {
      $command = trim($env_command) . ' ' . $command ;
    }
    $process = new Process($this->ensureCommandRunsViaBash($command));
    if ($this->workingDirectory) {
      $process->setWorkingDirectory($this->workingDirectory);
    }
    $exit_code = $process->run();

    if ($exit_code != 0) {
      throw new PhappEnvironmentUndefinedException();
    }
  }

  /**
   * {@inheritdoc}
   *
   * Customizes how commands are printed.
   */
  protected function getCommandDescription() {
    // Do not show wrapping the command in /bin/bash if not verbose.
    $is_verbose = Robo::getContainer()->get('output')->isVerbose();
    return "\n" . $this->getCommand($is_verbose);
  }

  /**
   * {@inheritdoc}
   */
  public function getCommand($run_via_bash = TRUE) {
    $command = parent::getCommand();

    // Support adding the environment, see ::addPhappEnvironment().
    if (isset($this->manifest)) {
      $this->ensureValidPhappEnvironment();

      if ($env_command = $this->manifest->getCommand('environment')) {
        $command = trim($env_command) . " && " . $command;
      }
    }

    return $run_via_bash ? $this->ensureCommandRunsViaBash($command) : $command;
  }

  /**
   * Modifies the command string so it will run via bash.
   */
  protected function ensureCommandRunsViaBash($command) {
    // Be sure to run commands via the shell. We need to escapes single quotes
    // in the command!
    return '/bin/bash -c ' . escapeshellarg($command);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute($process, $output_callback = NULL) {

    // If PHAPP_ENV etc. is already set, be sure to keep that.
    $process->inheritEnvironmentVariables(TRUE);

    return parent::execute($process, $output_callback);
  }

}
