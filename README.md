# Phapp CLI

Provides standardized console commands for PHP applications.

## Requirements

 * Git version >= 2.0

## Installation

Installation requires composer. As there are some dependency conflicts with the
latest drush release it is recommended to install the tool via consolidation/cgr.
To do so, just execute:

    # Download latest stable release.
    php -r "
      ini_set('user_agent','Mozilla/4.0 (compatible; MSIE 6.0)');
      readfile(json_decode(file_get_contents('https://api.github.com/repos/drunomics/phapp-cli/releases/latest'))->assets[0]->browser_download_url);
    " > phapp
    chmod +x phapp
    
    # Optional: Ensure ~/bin exists and configure it to be available in $PATH.
    [ -d ~/bin ] || mkdir ~/bin
    echo $PATH | grep -q ~/bin || (echo "export PATH=~/bin:\$PATH" >> ~/.bashrc && export PATH=~/bin:$PATH)
    
    # Make phapp executable from everywhere by moving to a destination available in $PATH.
    # If you skipped the optional step above, be sure to move it to a suiting destination.
    mv phapp ~/bin/phapp
 
## Updating

Run
      
     phapp self:update
     
## Usage

Run `phapp list` to show a list of support commands. A few important commands
are highlighted below:


### build

  - Build the project as configured in phapp.yml:
  
        phapp build
        
    If no build command configured, phapp will just run a composer install.

  - Build a certain branch and commit the build result in `build/{{ branch }}`:
  
        phapp build {{ branch }}

## Phapp development

### Build a new phar

The phar is built using box, for details see
https://github.com/box-project/box2. To built the phar just run:

     composer install --dev
     composer build

### Create a new release

* Tag a new version and push it.
* Build a new phar (see above).
* Upload the new phar at the github release page. Keep the filename as is.
* Note that the packagist API is not updated immediately. Thus it takes a few
  minutes until the new release is picked up by the self:update command.
