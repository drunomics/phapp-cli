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
    $default_config = $yamlParser->parse(file_get_contents(__DIR__ . '/../defaults/config.defaults.yml'));
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

  /**
   * Gets defaults for new phapp definition files (phapp.yml)
   *
   * @return mixed[]
   */
  public function getPhappDefinitionDefaults() {
    return $this->config['phapp_init_defaults'];
  }

  /**
   * Gets the URL for an extra composer package repository, if any.
   *
   * @return string|null
   */
  public function getComposerRepository() {
    return $this->config['phapp_discovery']['composer_repository'];
  }

  /**
   * Gets the default pattern for Git repository URLs of phapp projects.
   *
   * May contain the replacement token {{ phapp_name }}.
   *
   * @param string $phapp_name
   *   (optional) If given, replacement tokens are replaced using the given
   *   name.
   *
   * @return string
   */
  public function getGitUrlPattern($phapp_name = NULL) {
    if (isset($phapp_name)) {
      return strtr($this->config['phapp_discovery']['git_url_pattern'], [
        '{{ phapp_name }}' => $phapp_name,
      ]);
    }
    else {
      return $this->config['phapp_discovery']['git_url_pattern'];
    }
  }

  /**
   * Gets the default package vendor.
   *
   * @return string
   */
  public function getDefaultPackageVendor() {
    return $this->config['phapp_discovery']['package_vendor_default'];
  }

  /**
   * Gets the array of phapp template packages.
   *
   * @return string[]
   *   An array with the package names as keys and a human readable description
   *   as value.
   */
  public function getPhappTemplatePackages() {
    return $this->config['phapp_templates'];
  }

  /**
   * Gets the default path to the directory of cloned or created projects.
   *
   * May contain the replacement token {{ phapp_name }}.
   *
   * @param string $phapp_name
   *   (optional) If given, replacement tokens are replaced using the given
   *   name.
   *
   * @return string
   */
  public function getDefaultDirectoryPath($phapp_name) {
    if (isset($phapp_name)) {
      return strtr($this->config['phapp_default_directory_path'], [
        '{{ phapp_name }}' => $phapp_name,
      ]);
    }
    else {
      return $this->config['phapp_default_directory_path'];
    }
  }

}
