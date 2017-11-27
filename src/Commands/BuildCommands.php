<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\PhappCommandBase;
use drunomics\Phapp\ServiceUtil\GitCommandsTrait;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Builds an app.
 */
class BuildCommands extends PhappCommandBase {

  use GitCommandsTrait;

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
    $this->getGitCommands()->ensureGitWorkspaceIsClean();

    $previous_branch = $this->_execSilent("git rev-parse --abbrev-ref HEAD")->getOutput();
    $buildBranch = $this->phappManifest->getGitBranchForBuild($branch);
    $branchEscaped = escapeshellarg($branch);
    $collection = $this->collectionBuilder();
    $symfony_fs = new \Symfony\Component\Filesystem\Filesystem();

    // Add the rollback action.
    $collection->rollback($this->taskGitStack()
      ->exec('git reset --hard')
      ->checkout($previous_branch));

    // Make sure the target branch exists.
    if ($this->_execSilent("git branch --list $branchEscaped | grep $branchEscaped")->getExitCode() != 0) {
      throw new InvalidArgumentException("The branch $branchEscaped does not exist.");
    }

    // Update the branch and build branch.
    $this->getGitCommands()->setupGitRemotes(['fetch' => TRUE]);
    $collection->addTask(
      $this->getGitCommands()->pullBranch($branch)
    );

    // Make sure the build branch exists.
    $branch_exists = $this->_execSilent("git branch -a --list | grep $buildBranch -q")->getExitCode() == 0;

    if ($branch_exists) {
      $task = $this->taskGitStack()
        ->checkout($buildBranch)
        ->exec('reset --hard')
        ->merge($branch);
      $collection->addTask($task);
    }
    else {
      $ancestorBranch = $this->determineBuildBranchAncestor($branch, $buildBranch);
      $collection->addCode(function() use ($ancestorBranch) {
        $this->say("Creating a new build branch based upon <info>$ancestorBranch</info>");
      });
      // Update the ancestor branch.
      $collection->addTask(
        $this->getGitCommands()->pullBranch($ancestorBranch)
      );
      // Create the build branch and merge in changes.
      $task = $this->taskGitStack()
        ->exec("checkout -b $buildBranch")
        ->merge($branchEscaped);
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
      ->commit(sprintf("Build %s commit %s.", trim($branchEscaped, '\''), trim($commit_hash, '\'')), '--allow-empty --no-verify');

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
      $result = $this->_execSilent("git tag --points-at $branchEscaped | grep $prefix");
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
   * Determines the ancestor for the new build branch.
   *
   * @param string $branch
   *   The to be built branch.
   *
   * @return string
   *   The build branch upon which to base the new build branch.
   */
  protected function determineBuildBranchAncestor($branch) {
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
   * @command build:clean
   */
  public function clean() {
    $command = $this->phappManifest->getCommand('clean');
    if (!$command) {
      // Default to cleaning composer vendor directory.
      return $this->_exec("rm -rf ./vendor");
    }
    else {
      return $this->_exec($command);
    }
  }

}
