<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\PhappCommandBase;
use Symfony\Component\Process\Process;

/**
 * Clones an app.
 */
class CloneCommands extends PhappCommandBase {

  /**
   * {@inheritdoc}
   */
  protected $requiresPhappManifest = FALSE;

  /**
   * Clones a Phapp project.
   *
   * @param string $name
   *   The name of the phapp project to clone.
   * @param string $target
   *   (optional) The directory to clone to. If not given, the phapp project is
   *   cloned to the default directory path.
   * @option repository The Git repository URL to clone from. If not
   *   given, the repository URL is determined using the globally configured Git
   *   URL pattern.
   * @option branch The branch to clone. Defaults to the repository default
   *   branch.
   *
   * @throws \Exception
   *   Thrown if there are troubles cloning the repository.
   *
   * @command clone
   */
  public function execute($name, $target = NULL, $options = ['repository' => NULL, 'branch' => NULL]) {
    if (!isset($options['repository'])) {
      // @todo: Also check for a composer package with the default vendor.
      $options['repository'] = $this->globalConfig->getGitUrlPattern($name);
    }
    if (!isset($target)) {
      $target = $this->globalConfig->getDefaultDirectoryPath($name);
    }
    $args = isset($options['branch']) ? ' --branch='. $options['branch'] : '';
    $command = "git clone ${options['repository']} $target" . $args;

    // Make sure the command output is streamed while clone a repo.
    $this->logger->info('Running ' . $command);
    $process = Process::fromShellCommandline($command, null, getenv());
    $process->enableOutput()->start();
    $process->setTty(TRUE);
    $process->setTimeout(3600);
    $process->setIdleTimeout(120);

    foreach ($process as $output) {
      echo $output;
    }
    if ($process->getExitCode() != 0) {
      throw new \Exception('Error cloning repository.');
    }
  }

}
