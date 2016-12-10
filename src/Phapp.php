<?php

namespace drunomics\Phapp;

use drunomics\Phapp\Exception\PhappInstanceNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Parser;

/**
 * Provides information about a local phapp instance.
 */
class Phapp {

  /**
   * The config file.
   *
   * @var \Symfony\Component\Finder\SplFileInfo
   */
  protected $configFile;

  /**
   * The content of the config file.
   *
   * @var mixed[]
   */
  protected $config;

  /**
   * An array of default config.
   *
   * @var array
   */
  static protected $configDefaults = [
    'composer-bin' => 'composer',
    'commands' => [
      'build' => 'composer install',
    ],
  ];

  /**
   * Finds a phapp directory based upon the current working directory.
   *
   * @return static|null
   *   The phapp instance or NULL if no instance can be found.
   *
   * @throws \Symfony\Component\Yaml\Exception\ParseException
   *   Thrown if the phapp.yml file found is invalid.
   */
  public static function discoverInstance() {
    $finder = new Finder();
    $yamlParser = new Parser();

    // @todo: Add multiple parent Git dirs here.
    $finder->files()->name('phapp.yml')->in(getcwd())->depth('== 0');

    if ($finder->count() == 0) {
      return;
    }
    // Just use the first phapp file found.
    foreach ($finder as $file) {
      break;
    }
    $config = $yamlParser->parse(file_get_contents($file->getRealPath()));
    return new static($config, $file);
  }

  /**
   * Gets a phapp instance based upon the current working directory.
   *
   * @return static
   *   The phapp instance.
   *
   * @throws \Symfony\Component\Yaml\Exception\ParseException
   *   Thrown if the phapp.yml file found is invalid.
   * @throws \drunomics\Phapp\Exception\PhappInstanceNotFoundException
   *   If no phapp.yml could be found.
   */
  public static function getInstance() {
    $phapp = static::discoverInstance();
    if (!$phapp) {
      throw new PhappInstanceNotFoundException();
    }
    return $phapp;
  }

  /**
   * Constructs the object.
   *
   * @param array $config
   *   The parsed phapp.yml file contents.
   * @param \Symfony\Component\Finder\SplFileInfo $configFile
   *   The config file info.
   */
  public function __construct(array $config, SplFileInfo $configFile) {
    $this->config = array_replace_recursive(static::$configDefaults, $config);
    $this->configFile = $configFile;
  }

  /**
   * Switches the working directory and adds the composer bin-dir to the path.
   *
   * @return $this
   */
  public function initShellEnvironment() {
    chdir($this->configFile->getPath());
    $path = getenv("PATH");
    putenv("PATH=../vendor/bin/:../bin:$path");
    return $this;
  }

  /**
   * Gets information about the phapp.yml file.
   *
   * @return \Symfony\Component\Finder\SplFileInfo
   */
  public function getConfigFile() {
    return $this->configFile;
  }

  /**
   * Gets the command for running composer. Usually "composer".
   *
   * @return string
   */
  public function getComposerBin() {
    return $this->config['composer-bin'];
  }

  /**
   * Gets the bash string configured for a given command.
   *
   * @param string $name
   *   The name of the command; e.g. 'build' or 'deploy'.
   *
   * @return string|null
   *   The configured command string.
   */
  public function getCommand($name) {
    if (isset($this->config['commands'][$name])) {
      return $this->config['commands'][$name];
    }
    return NULL;
  }

}
