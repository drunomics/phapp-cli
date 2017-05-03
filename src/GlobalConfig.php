<?php

namespace drunomics\Phapp;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

/**
 * Provides system-wide configuration.
 */
class GlobalConfig {

  /**
   * The content of the config file.
   *
   * @var mixed[]
   */
  protected $config;

  /**
   * Finds the system-wide config.
   *
   * @return static|null
   *   The global config or NULL if no instance can be found.
   *
   * @throws \Symfony\Component\Yaml\Exception\ParseException
   *   Thrown if the phapp.yml file found is invalid.
   */
  public static function discoverConfig() {
    $finder = new Finder();
    $yamlParser = new Parser();

    $search_dirs = [getcwd()];
    if (is_dir('~/.phapp')) {
      $search_dirs[] = '~/.phapp';
    }
    if (is_dir('/etc/phapp')) {
     $search_dirs[] = 'etc/phapp';
    }
    $finder->files()->name('config.yml')->in($search_dirs)->depth('== 0');

    if ($finder->count() != 0) {
      // Just use the first file found.
      foreach ($finder as $file) {
        break;
      }
      $file_path = $file->getRealPath();
      $config = $yamlParser->parse(file_get_contents($file_path));
    }
    else {
      $config = [];
    }
    return new static($config);
  }

  /**
   * Constructs the object.
   *
   * @param array $config
   *   The parsed phapp.yml file contents.
   */
  public function __construct(array $config) {
    $yamlParser = new Parser();
    $default_config = $yamlParser->parse(file_get_contents(__DIR__ . '/../config.defaults.yml'));
    $this->config = array_replace_recursive($default_config, $config);
  }

  /**
   * Gets the command for running composer. Usually "composer".
   *
   * @return string
   */
  public function getComposerBin() {
    return $this->config['command_bin']['composer'];
  }

}
