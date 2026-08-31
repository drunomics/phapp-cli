<?php

namespace drunomics\Phapp;

use Composer\Script\Event;
use Composer\Util\StreamContextFactory;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Scripthandler for installing (development) tools.
 */
class ScriptHandler {

  /**
   * Install phar tools as noted in the extra tools section.
   *
   * This is like tm/tooly-composer-script but faster.
   *
   * @param Event $event
   */
  public static function installPharTools(Event $event) {

    if ($event->isDevMode()) {
      $fs = new Filesystem();
      $composer = $event->getComposer();
      $bin_dir = $composer->getConfig()->get('bin-dir');
      $extras = $composer->getPackage()->getExtra();

      if (array_key_exists('tools', $extras)) {
        if (!$fs->exists($bin_dir)) {
          $fs->mkdir($bin_dir);
        }
        foreach ($extras['tools'] as $tool => $data) {
          if (empty($data['url'])) {
            throw new \LogicException("Missing tool url.");
          }
          $filename = basename($data['url']);
          // The URL of the currently installed phar is recorded, such that the
          // tool is re-installed whenever the pinned URL changes. Checking for
          // the file only is not sufficient, since a new version may well be
          // published under the same file name.
          $stamp_file = "$bin_dir/.$tool.url";
          $installed_url = $fs->exists($stamp_file) ? trim(file_get_contents($stamp_file)) : NULL;

          if ($installed_url !== $data['url'] || !$fs->exists("$bin_dir/$filename")) {
            $event->getIO()->write("<info>Downloading $filename...</info>");
            $content = static::download($data['url']);
            $fs->dumpFile("$bin_dir/$filename", $content);
            $fs->chmod("$bin_dir/$filename", 0755);
            // Clean up the phar of the previously pinned version.
            if ($installed_url && basename($installed_url) !== $filename) {
              $fs->remove("$bin_dir/" . basename($installed_url));
            }
            $fs->dumpFile($stamp_file, $data['url']);
          }

          // The symlink must be updated unconditionally: when the pinned URL
          // changes it still points to the previously installed version. It is
          // kept relative, so it keeps working when the project directory is
          // mounted at another path; e.g. inside a container.
          $link = "$bin_dir/$tool";
          if ($fs->exists($link) && !is_link($link)) {
            $fs->remove($link);
          }
          $fs->symlink($filename, $link);
        }
      }
    }
  }

  /**
   * @param string $url
   *
   * @return string
   */
  protected static function download($url) {
    $context = StreamContextFactory::getContext($url);
    return file_get_contents($url, FALSE, $context);
  }
}
