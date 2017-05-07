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
   * The global phapp config.
   *
   * @var \drunomics\Phapp\GlobalConfig
   */
  protected $globalConfig;

  /**
   * The maniftest of the current phapp instance.
   *
   * @var \drunomics\Phapp\PhappManifest
   */
  protected $phappManifest;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->stopOnFail(TRUE);
  }

  /**
   * Ensures with a valid phapp definition to interact with.
   *
   * @todo: Move phappManifest and globalConfig to services.
   *
   * @hook validate
   */
  public function initPhapp() {
    $this->phappManifest = PhappManifest::getInstance();
    $this->phappManifest->initShellEnvironment();
    $this->globalConfig = GlobalConfig::discoverConfig();
  }

}
