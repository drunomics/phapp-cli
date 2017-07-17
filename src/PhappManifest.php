<?php

namespace drunomics\Phapp;

use drunomics\Phapp\Exception\PhappInstanceNotFoundException;
use drunomics\Phapp\Exception\PhappManifestMalformedException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Parser;

/**
 * Provides information about a local phapp instance.
 */
class PhappManifest {

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
    $yamlParser = new Parser();
    $default_config = $yamlParser->parse(file_get_contents(__DIR__ . '/../defaults/phapp.defaults.yml'));
    $this->config = array_replace_recursive($default_config, $config);
    $this->file = $configFile;
    $this->validate();
  }

  /**
   * Validates the phapp manifest.
   *
   * @throws PhappManifestMalformedException
   *   Thrown when validation fails.
   */
  public function validate() {
    if (empty($this->config['name'])) {
      throw new PhappManifestMalformedException('Phapp name is required.');
    }
    if (!preg_match('/^[a-z0-9_-]+$/', $this->config['name'])) {
      throw new PhappManifestMalformedException('Phapp name may only contain lowercase alpha-numeric characters, dashes and underscores.');
    }
  }

  /**
   * Gets information about the phapp.yml file.
   *
   * @return \Symfony\Component\Finder\SplFileInfo
   */
  public function getFile() {
    return $this->file;
  }

  /**
   * Gets the app's name.
   *
   * @return string
   */
  public function getName() {
    return $this->config['name'];
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

  /**
   * Gets the Git repository URL.
   *
   * @return string
   */
  public function getGitUrl() {
    return $this->config['git']['url'];
  }

  /**
   * Gets the URLs of all Git repository mirrors, if any.
   *
   * @return string[]
   */
  public function getGitMirrors() {
    return $this->config['git']['mirrors'];
  }

  /**
   * Gets the name of the production branch, usually 'master'.
   *
   * @return string
   */
  public function getGitBranchProduction() {
    return $this->config['git']['branches']['production'];
  }

  /**
   * Gets the prefix used for the tags of released versions.
   *
   * @return string
   */
  public function getGitVersionTagPrefix() {
    return $this->config['git']['branches']['version_prefix'];
  }

  /**
   * Gets the name of the respective build branch, usually 'build/$BRANCH'.
   *
   * @return string
   */
  public function getGitBranchForBuild($source_branch) {
    return $this->config['git']['branches']['build_prefix'] .  $source_branch;
  }

  /**
   * Gets the name of the develop branch, usually 'develop'.
   *
   * @return string
   */
  public function getGitBranchDevelop() {
    return $this->config['git']['branches']['develop'];
  }

}
