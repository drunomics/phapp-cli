<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\PhappCommandBase;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Builds an app.
 */
class BuildCommand extends PhappCommandBase {

  /**
   * Builds the project with the current code checkout.
   *
   * @option $clean Allows starting the build from a clean state. If specified,
   *   any previously installed composer dependencies are removed.
   *
   * @return \Robo\Collection\Collection
   *
   * @command build
   */
  public function build($options = ['clean' => FALSE]) {
    return $this->doBuild($options);
  }

  /**
   * Builds the project with the current code checkout.
   *
   * @param $options
   *   The command options.
   *
   * @return \Robo\Collection\Collection
   *   The collection containing the build commands.
   */
  protected function doBuild($options) {
    $collection = $this->collectionBuilder();

    if ($options['clean']) {
      $collection->addTask($this->clean());
    }

    $collection->addTaskToCollection(
      $this->taskExec($this->phappManifest->getCommand('build'))
    );

    // Avoid problems with git submodules.
    $collection->addCode(
      function() {
        $finder = new Finder();
        $directories = $finder->directories()
          ->name('.git')
          ->ignoreVCS(FALSE)
          ->ignoreDotFiles(FALSE)
          ->in(getcwd());

        $dirs = [];
        foreach ($directories as $dir) {
          if ($dir->getRelativePath() != '') {
            $dirs[] = $dir->getPathname();
          }
        }
        if ($dirs) {
          $this->say("Removing .git directories to avoid troubles with git submodules");
          $this->taskDeleteDir($dirs)->run();
        }
      }
    );

    return $collection;
  }

  /**
   * Builds a given branch.
   *
   * The build is going to be committed to the respective build branch.
   *
   * @param string $branch
   *   The branch to check out and build.
   * @option $clean Cleans data from previous builds before starting the build.
   *
   * @return \Robo\Collection\Collection
   *
   * @command build:branch
   */
  public function buildBranch($branch, $options = ['clean' => TRUE]) {
    $this->stopOnFail(TRUE);

    $previous_branch = $this->_execSilent("git rev-parse --abbrev-ref HEAD")->getOutput();
    $buildBranch = escapeshellarg($this->phappManifest->getGitBranchForBuild($branch));
    $branch = escapeshellarg($branch);
    $collection = $this->collectionBuilder();
    $symfony_fs = new \Symfony\Component\Filesystem\Filesystem();

    // Add the rollback action.
    $collection->rollback($this->taskGitStack()
      ->exec('git reset --hard')
      ->checkout($previous_branch));

    // Make sure the target branch exists.
    if ($this->_execSilent("git branch --list $branch | grep $branch")->getExitCode() != 0) {
      throw new InvalidArgumentException("The branch $branch does not exist.");
    }

    // Make sure the build branch exists.
    $branch_exists = $this->_execSilent("git branch --list $buildBranch | grep $buildBranch")->getExitCode() == 0;

    if ($branch_exists) {
      // Update the build branch.
      $task = $this->taskGitStack()
        ->checkout($buildBranch)
        ->exec('reset --hard');
      // Only pull from the remote if the remote branch exists.
      $remote = $this->phappManifest->getGitUrl() ?: 'origin';
      if ($this->_execSilent("git fetch $remote && git branch -r --contains $buildBranch")->getOutput()) {
        $task->pull($remote, $buildBranch);
      }
      $task->merge($branch);
      $collection->addTask($task);
    }
    else {
      $sourceBranch = $this->determineBuildBranchSource($branch, $buildBranch);
      $collection->addCode(function() use ($sourceBranch) {
        $this->say("Creating a new build branch based upon <info>$sourceBranch</info>");
      });
      // Update the source branch.
      $task = $this->taskGitStack()
        ->checkout($sourceBranch)
        ->exec('reset --hard');
      // Only pull if the remote branch exists.
      $remote = $this->phappManifest->getGitUrl() ?: 'origin';
      if ($this->_execSilent("git fetch $remote && git branch -r --contains $sourceBranch")->getOutput()) {
        $task->pull($remote, $sourceBranch);
      }
      // Create the build branch and merge in changes.
      $task
        ->exec("checkout -b $buildBranch")
        ->merge($branch);
      $collection->addTask($task);
    }

    // Handle .gitignore.
    if ($symfony_fs->exists('.build-gitignore')) {
      $this->say('Found .build-gitingore - applying it.');
      $collection->addTask(
        $this->taskExec('cp .build-gitignore .gitignore')
      );
    }
    elseif ($symfony_fs->exists('.gitignore')) {
      $collection->addTask(
        $this->taskExec('rm .gitignore')
      );
    }

    // Now, as we are on a the clean build branch, start the build.
    $collection->addTask(
      $this->doBuild($options)
    );

    // And then commit it!
    $commit_hash = $this->_execSilent("git rev-parse HEAD")->getOutput();
    $commit_hash = trim($commit_hash);

    $task = $this->taskGitStack()
      ->exec("add -A")
      // Note that git commit uses ' already, so we remove ours. Also, we allow
      // empty commits in case no assets were changed the merge might be enough.
      ->commit(sprintf("Build %s commit %s.", trim($branch, '\''), trim($commit_hash, '\'')), '--allow-empty --no-verify');

    $collection->addTask($task);

    $collection->completionCode(
      function() use ($buildBranch) {
        $this->yell("Committed build to branch $buildBranch.");
      }
    );

    // Fetch the version tag.
    $tag = FALSE;
    if ($prefix = $this->phappManifest->getGitVersionTagPrefix()) {
      $prefix = escapeshellarg($prefix);
      $result = $this->_execSilent("git tag --points-at $branch | grep $prefix");
      if ($result->getExitCode() == 0) {
        $tag = $result->getOutput();
      }
    }

    // Automatically forward version tags to builds. We only supported
    // forwarding tags when there is a build branch prefix.
    if ($tag && $this->phappManifest->getGitBranchForBuild($tag) != $tag) {
      $target_tag = $this->phappManifest->getGitBranchForBuild($tag);

      // Ensure the target tag is not already existing (e.g. if building the
      // same commit multiple times).
      $process = $this->_execSilent("git show -q $target_tag");
      if (!$process->isSuccessful()) {
        $collection->addTask(
          $this->taskGitStack()
            ->tag($target_tag)
        );
        $collection->completionCode(
          function() use ($target_tag) {
            $this->say("Tagged build as $target_tag");
          }
        );
      }
      else {
        $collection->completionCode(
          function() use ($target_tag) {
            $this->io()->warning("Tag $target_tag already exists - the tag remains unchanged.");
          }
        );
      }
    }

    // Restore .gitignore and previous branch.
    $collection->completion(
      $this->taskGitStack()
        ->exec('git reset --hard')
        ->checkout($previous_branch)
    );
    return $collection;
  }

  /**
   * Determines the source branch for the new build branch.
   *
   * @param string $branch
   *   The to be built branch.
   *
   * @return string
   */
  protected function determineBuildBranchSource($branch) {
    // Check whether the latest build of the production branch is something we
    // can start from with.
    $productionBranch = $this->phappManifest->getGitBranchProduction();
    $productionBuildBranch = $this->phappManifest->getGitBranchForBuild($productionBranch);
    $process = $this->_execSilent("git log --format=oneline $productionBuildBranch --grep \"Build $productionBranch commit \"");

    if ($process->isSuccessful() && $output = $process->getOutput()) {
      // Parse the message to get the source commit hash.
      list($first_line) = explode("\n", $output, 2);
      $matches = [];
      if (preg_match('/Build ' . $productionBranch . ' commit (\S*)./', $first_line, $matches)) {
        $sourceCommit = $matches[1];
      }
    }

    // If the source commit of the last build has been found, verify the
    // to-be-built branch is based upon it. Else, we need to start a new build
    // branch.
    if (!empty($sourceCommit)) {
      $process = $this->_execSilent("git log --format=oneline $sourceCommit..$branch");
      $sourceIsParent = $process->isSuccessful() && $process->getOutput() != '';

      if ($sourceIsParent) {
        return $productionBuildBranch;
      }

      // Check whether the source commit is the same as to-be-built commit.
      $process = $this->_execSilent("git reflog $sourceCommit");
      $hash1 = $process->isSuccessful() ? $process->getOutput() : FALSE;
      $process = $this->_execSilent("git reflog $branch");
      $hash2 = $process->isSuccessful() ? $process->getOutput() : FALSE;
      if ($hash1 && $hash2 && $hash1 == $hash2) {
        return $productionBuildBranch;
      }
    }

    // No relationship between the to-be-built and the last built commit could
    // be found. Thus, start a new build branch based upon the to-be-built
    // branch.
    return $branch;
  }

  /**
   * Cleans all build related files.
   *
   * Removes all dependencies that are installed via composer.
   *
   * @todo: Make cleaning builds customizable.
   *
   * @command build:clean
   */
  public function clean() {
    $composer = $this->globalConfig->getComposerBin();
    $process = new Process("$composer show --path");
    $process->run();

    if (!$process->isSuccessful()) {
      throw new \Exception("Errors while running $composer." . $process->getErrorOutput());
    }

    $dirs = [];
    foreach (explode("\n", $process->getOutput()) as $line) {
      $matches = [];
      // Parse out the path from the output. The path is the second "word",
      // after the package and some whitespace.
      if (preg_match('/\S*\s*(\S*)/', $line, $matches)) {
        $dirs[] = $matches[1];
      }
    }
    // Remove dirs which are sub-directories of the vendor dirs and delete that
    // instead.
    $vendor_dir = realpath('./vendor');
    $dirs = array_filter($dirs, function($dir) use ($vendor_dir) {
      return $dir && strpos($dir, $vendor_dir) !== 0;
    });
    $dirs[] = $vendor_dir;

    // Cut of the current directory and use relative paths.
    $cwd = realpath(getcwd());
    foreach ($dirs as &$dir) {
      $dir = str_replace($cwd . '/', '', $dir);
    }
    return $this->taskDeleteDir($dirs);
  }
}
