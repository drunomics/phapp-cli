<?php

namespace drunomics\Phapp;

use drunomics\Phapp\Exception\PhappInstanceNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

/**
 * Provides information about a local phapp instance.
 */
class Phapp {

  /**
   * The content of the config file.
   *
   * @var mixed[]
   */
  protected $config;

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
    return new static($config);
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
   */
  public function create(array $config) {
    $this->config = $config;
  }

}
