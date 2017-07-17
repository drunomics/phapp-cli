<?php

namespace drunomics\Phapp;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Tasks;

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
   * Switches the working directory and adds the composer bin-dir to the path.
   *
   * @return $this
   */
  protected function initShellEnvironment() {
    chdir($this->phappManifest->getConfigFile()->getPath());
    $path = getenv("PATH");
    putenv("PATH=../vendor/bin/:../bin:$path");
    return $this;
  }

}
