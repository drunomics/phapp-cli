<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\PhappCommandBase;
use Robo\Contract\TaskInterface;

/**
 * Class CloneCommand.
 */
class CloneCommand extends PhappCommandBase {

  /**
   * Clones a phapp project.
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
   * @command clone
   *
   * @return \Robo\Result
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

    return $this->_exec("git clone ${options['repository']} $target" . $args);
  }

}
