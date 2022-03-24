<?php

namespace drunomics\Phapp;

use drunomics\Phapp\Exception\PhappManifestMalformedException;
use drunomics\Phapp\Task\Exec;
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
   * The manifest of the current phapp instance.
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
    return $this;
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
    $process = Process::fromShellCommandline($command);
    $process->run();
    return $process;
  }

  /**
   * Invokes a command from the phapp manifest.
   *
   * @param string $command_name
   *   The name of the command to invoke; e.g. 'setup'.
   *
   * @return \Robo\Contract\TaskInterface
   *   The task for running the command.
   *
   * @throws \drunomics\Phapp\Exception\PhappEnvironmentUndefinedException
   *   Thrown if some phapp manifest reference is invalid.
   * @throws \drunomics\Phapp\Exception\PhappManifestMalformedException
   *   Thrown if some referenced phapp manifest is malformed.
   */
  protected function invokeManifestCommand($command_name) {
    $collection = $this->collectionBuilder();

    $command = $this->phappManifest->getCommand($command_name);
    if (!$command) {
      $collection->addCode(function() use ($command_name) {
        $this->say("Command <info>$command_name</info> is undefined, skipping.");
      });
    }
    else {
      // Support passing on commands to the sub-apps.
      if (strpos(trim($command), '@sub-apps') === 0) {
        $sub_app_dirs = $this->phappManifest->getSubAppDirectories();

        foreach ($sub_app_dirs as $dir) {
          $collection->addTask(
            $this->invokeManifestCommandAtDirectory($command_name, $dir)
          );
        }
      }
      // Support @phapp:path/to/directory references.
      elseif (strpos(trim($command), '@phapp:') === 0) {
        $dir = substr(trim($command), 7);
        if (!is_dir($dir)) {
          throw new PhappManifestMalformedException("Invalid directory given in reference $command");
        }
        $collection->addTask(
          $this->invokeManifestCommandAtDirectory($command_name, $dir)
        );
      }
      else {
        // Directly execute the given command.
        // @todo: Ensure the command is run via bash.
        $collection->addTask(
          $this->taskExec($command)
            ->addPhappEnvironment($this->phappManifest)
        );
      }
    }
    return $collection;
  }


  /**
   * Invokes a manifest command defined at the app in the given directory.
   *
   * @param string $command_name
   *   The command name.
   * @param string $directory
   *   The relative directory of the app.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   The tasks to execute
   *
   * @throws \drunomics\Phapp\Exception\PhappEnvironmentUndefinedException
   *   Thrown if no phapp manifest can be found at the given directory.
   */
  protected function invokeManifestCommandAtDirectory($command_name, $directory) {
    $collection = $this->collectionBuilder();
    $manifest = PhappManifest::getInstance($directory);

    $collection->addCode(function() use ($command_name, $manifest) {
      $this->say("Executing <info>$command_name</info> for app <info>{$manifest->getName()}</info>" );
    });

    $collection->addTask(
      $this->taskExec($manifest->getCommand($command_name))
        ->dir($directory)
        ->addPhappEnvironment($manifest)
    );

    return $collection;
  }

  /**
   * {@inheritdoc}
   *
   * Use our own version of the Exec task.
   */
  protected function taskExec($command) {
    return $this->task(Exec::class, $command);
  }

}
