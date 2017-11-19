<?php

namespace drunomics\Phapp;

use drunomics\Phapp\Exception\PhappEnvironmentUndefinedException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Tasks;
use Symfony\Component\Process\Process;

/**
 * Base class for phapp command classes.
 */
abstract class PhappCommandBase extends Tasks implements LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * Whether the command requires a valid phapp manifest.
   *
   * @var bool
   */
  protected $requiresPhappManifest = TRUE;

  /**
   * The maniftest of the current phapp instance.
   *
   * @var \drunomics\Phapp\PhappManifest|null
   */
  protected $phappManifest;

  /**
   * The global phapp config.
   *
   * @var \drunomics\Phapp\GlobalConfig
   */
  protected $globalConfig;

  /**
   * Ensures with a valid phapp definition to interact with.
   *
   * @hook validate
   */
  public function init() {
    $this->globalConfig = GlobalConfig::discoverConfig();
    if ($this->requiresPhappManifest) {
      $this->phappManifest = PhappManifest::getInstance();
      $this->initShellEnvironment();
    }
    $this->stopOnFail(TRUE);
  }

  /**
   * Initializes the shell environment.
   *
   * Switches the working directory, initializes all phapp environment variables
   * and adds the composer bin-dir to the path.
   *
   * @return $this
   */
  protected function initShellEnvironment() {
    // Switch working directory.
    chdir($this->phappManifest->getFile()->getPath());
    // Add the composer bin-dir to the path.
    $path = getenv("PATH");
    putenv("PATH=../vendor/bin/:../bin:$path");
    $this->initPhappEnviromentVariables();
    return $this;
  }

  /**
   * Initializes all phapp environment variables.
   *
   * @throws \drunomics\Phapp\Exception\PhappEnvironmentUndefinedException
   *   Thrown when the environment is undefined.
   */
  protected function initPhappEnviromentVariables() {
    // @todo: Read .env via symfony dotenv here.
    if (!getenv('PHAPP_ENV')) {
      throw new PhappEnvironmentUndefinedException();
    }
  }

  /**
   * Silently execute a command in bash.
   *
   * @param string $command
   *   The command.
   *
   * @return \Symfony\Component\Process\Process
   */
  protected function _execSilent($command) {
    // @todo: Enforce piping the command through bash if the active shell is not
    // bash.

    // Note that we cannot execute the task as regulary as this prints bold
    // red warnings when we do not want it to AND it stops on fails!
    // Because of that we execute the command directly with the symfony process
    // helper.
    $process = new Process($command);
    $process->run();
    return $process;
  }

  /**
   * Executes a command in bash.
   *
   * @param string $command
   *   The command to execute.
   *
   * @return \Robo\Result
   */
  protected function _exec($command) {
    // @todo: Enforce piping the command through bash if the active shell is not
    // bash.
    return parent::_exec($command);
  }


}
