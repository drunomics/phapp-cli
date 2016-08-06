<?php

namespace drunomics\Phapp;

use Consolidation\AnnotatedCommand\AnnotatedCommandFactory;
use Consolidation\OutputFormatters\FormatterManager;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Main phapp cli app.
 */
class PhappApp extends  Application {

  /**
   * {@inheritdoc}
   */
  public function run(InputInterface $input = null, OutputInterface $output = null) {
    $commandFactory = new AnnotatedCommandFactory();
    $commandFactory
      ->commandProcessor()
      ->setFormatterManager(new FormatterManager());
    $commandList = $commandFactory->createCommandsFromClass(SetupCommands::class);
    foreach ($commandList as $command) {
      // Add default-format to all commands.
      $description = 'The output format. Available formats are: json, yaml, print-r, list.';
      $command->addOption('format', 'f', InputOption::VALUE_REQUIRED, $description, 'json');
      $this->add($command);
    }
    $this->setName("Phapp CLI\nCopyright (C) drunomics GmbH");
    $this->setDefaultCommand('list');
    $this->setAutoExit(false);
    return parent::run();
  }

}
