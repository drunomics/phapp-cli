<?php

namespace drunomics\Phapp;

use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use Robo\Application;
use Robo\Robo;
use Robo\Runner;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * A runner for phapp that customizes defaults.
 */
class PhappRunner extends Runner {

  /**
   * Initializes the container.
   */
  protected function initContainer(Application $app) {
    // @todo: Add support for system-level command config.
    $container = Robo::createDefaultContainer($this->input(), $this->output(), $app);

    // Only register explict annotated commands.
    $container->get('commandFactory')
      ->setIncludeAllPublicMethods(FALSE);

    $this->setContainer($container);
    $this->installRoboHandlers();
    Robo::setContainer($container);
  }

  /**
   * Discovers command classes.
   *
   * @return array
   */
  protected function discoverCommands() {
    $discovery = new CommandFileDiscovery();
    $discovery->setSearchPattern('*Commands.php');
    return $discovery->discover(__DIR__ . '/Commands', '\drunomics\Phapp\Commands');
  }

  /**
   * {@inheritdoc}
   */
  public function run($input = NULL, $output = NULL, $app = NULL, $commandFiles = [], $classLoader = NULL) {
    if (!$input) {
      $input = new ArgvInput($_SERVER['argv']);
    }
    if (!$output) {
      $output = new ConsoleOutput();
    }
    if (!$app) {
      $app = Robo::createDefaultApplication();
    }
    if (!$commandFiles) {
      $commandFiles = $this->discoverCommands();
    }
    $this->setInput($input);
    $this->setOutput($output);

    $this->initContainer($app);
    return parent::run($input, $output, $app, $commandFiles);
  }

}
