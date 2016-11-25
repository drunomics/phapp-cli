# Phapp CLI tool

Provides standardized console commands for PHP applications.

## Installation

Installation requires composer. As there are some dependency conflicts with the
latest drush release it is recommended to install the tool via consolidation/cgr.
To do so, just execute:

     composer global require consolidation/cgr
     export INSTALL_DIR=$HOME/.composer/global/drunomics/phapp-cli
     mkdir -p $INSTALL_DIR && echo "{}" > $INSTALL_DIR/composer.json
     composer --working-dir=$INSTALL_DIR config minimum-stability dev
     composer --working-dir=$INSTALL_DIR config prefer-stable true
     ~/.composer/vendor/bin/cgr drunomics/phapp-cli

Note: The code gets placed in 

  ~/.composer/global/drunomics/phapp-cli/vendor/drunomics/phapp-cli
  
and the "phapp" executable is added to the bin-dir (~/.composer/vendor/bin)
automatically. If not done yet, Make sure ~/.composer/vendor/bin is in your
PATH.
 
## Updating

Re-run
      
     cgr drunomics/phapp-cli
     
## Usage

Run `phapp list` to show a list of support commands. A few important commands
are highlighted below:


### build

  - Build the project as configured in phapp.yml:
  
        phapp build
        
    If no build command configured, phapp will just run a composer install.

  - Build a certain branch and commit the build result in `build/{{ branch }}`:
  
        phapp build {{ branch }}
