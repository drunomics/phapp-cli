# Phapp CLI

Provides standardized console commands for PHP applications.

## Requirements

 * Git version >= 2.0
 * Bash 4.*

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

Run `phapp list` to show a list of support commands and use `phapp command --help`
for more information about a command. The list of currently available commands
is:

      build                 Builds the project with the current code checkout.
      clone                 Clones a Phapp project.
      create                Creates a new project base on a given template.
      help                  Displays help for a command
      init                  Initializes the app.
      install               Installs the application.
      list                  Lists commands
      setup                 Setups the phapp environment.
      status                Checks for a working and installed application.
      update                Updates the app.
     build
      build:branch          Builds a given branch.
      build:clean           Cleans all build related files.
     git
      git:pull              Updates local branches by pull from remote repositories.
      git:setup-remotes     Configures Git remote repositories.
     init
      init:manifest         Initializes a new phapp.yml for your project.
     self
      self:rollback         Rolls back to the previous version after a self-update.
      self:update           Updates the installed phar.
      
## phapp.yml

An application provides basic metadata and customizes commands in its 
`phapp.yml`, the phapp manifest. See [examples/phapp.yml](https://github.com/drunomics/phapp-cli/blob/master/examples/phapp.yml)
for an example.
      
## Phapp environment variables

Commands defined in `phapp.yml` may make use of the phapp environment
variables. Phapp environment variables are either set by the environment; i.e.
the host, or via a [.env](https://symfony.com/doc/current/components/dotenv.html)
file.

Available environment variables are / must be:

Variable | Description | Example value |
--- | --- | --- |
| PHAPP_ENV       | The environment name. E.g., local, test or live | live |
| PHAPP_ENV_TYPE  | The environment type; e.g. an id for the hosting environment or type of server. | acquia |
| PHAPP_ENV_MODE  | The environment mode; valid values are: production, development | production |
| PHAPP_BASE_URL  | The base URL of the app. | https://example.com |

Optional environment variables are:

Variable | Description | Example value |
--- | --- | --- |
| PHAPP_ENV_COLOR | A color used for indicating the current environment. | 302f2f |

### Database connection.

    PHAPP_ENV_MYSQL_DEFAULT_DATABASE=database
    PHAPP_ENV_MYSQL_DEFAULT_USERNAME=user
    PHAPP_ENV_MYSQL_DEFAULT_PASSWORD=pass
    PHAPP_ENV_MYSQL_DEFAULT_HOST=localhost
    PHAPP_ENV_MYSQL_DEFAULT_PORT="3306"

### Various other variables provided by the environment.
    PHAPP_ENV_DUMP_DIR="/data/mysql_dumps/sync"
    PHAPP_ENV_DUMP_DB_FILENAME="${PHAPP_ENV_MYSQL_DEFAULT_DATABASE}-$(date -d "1 day ago" +%Y%m%d).sql.gz"

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
