<?php

namespace drunomics\Phapp;

use Consolidation\AnnotatedCommand\AnnotatedCommandFactory;
use Consolidation\OutputFormatters\FormatterManager;
use drunomics\Phapp\Commands\CreateCommand;
use League\Container\Container;
use Robo\Robo;
use Robo\Runner;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Main phapp cli app.
 */
class PhappApp extends Application {

  /**
   * {@inheritdoc}
   */
  public function run(InputInterface $input = null, OutputInterface $output = null) {
    $container = $this->initContainer();

    $commandFactory = new AnnotatedCommandFactory();
    $commandFactory
      ->commandProcessor()
      ->setFormatterManager(new FormatterManager());

    $commandObjects = [
      (new CreateCommand()),
    ];

    foreach ($commandObjects as $command) {
      // Register service providers from commands.
      $command->setContainer($container);
      foreach ($command->getServiceProviders() as $provider) {
        $container->addServiceProvider($provider);
      }

      $annotatedCommandList = $commandFactory->createCommandsFromClass($command);
      foreach ($annotatedCommandList as $annotatedCommand) {
        // Add default-format to all commands.
        $description = 'The output format. Available formats are: json, yaml, print-r, list.';
        $annotatedCommand->addOption('format', 'f', InputOption::VALUE_REQUIRED, $description, 'json');
        $this->add($annotatedCommand);
      }
    }

    $this->setName("Phapp CLI\nCopyright (C) drunomics GmbH");
    $this->setDefaultCommand('list');
    $this->setAutoExit(false);
    return parent::run();
  }

  /**
   * Initializes the container.
   */
  protected function initContainer(InputInterface $input = null, OutputInterface $output = null) {
    // If we were not provided a container, then create one
    if (!Robo::hasContainer()) {
      // Set up our dependency injection container.
      $container = new Container();
      Runner::configureContainer($container, $input, $output);
      Robo::setContainer($container);
    }
    return Robo::getContainer();
  }

}
