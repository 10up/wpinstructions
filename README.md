# WP Instructions

*THIS SOFTWARE IS BETA.*

WP Instructions lets you execute WordPress actions in human-readable format e.g. installing WordPress, activating plugins, etc. WP Instruction files can be provided to developers to ease local development setup and improve environment consistency.

## Requirements

* PHP 7.2+

## Install

Install WP Instructions with Composer like so:

```
composer require 10up/wpinstructions
```

## Usage

Instructions should be saved in a file named `WPInstructions`. Here is an example of an instructions file:

```
install wordpress where url is http://wp.test
Install plugin where name is distributor and url is https://github.com/10up/distributor.git and version is latest
install theme where name is twentynineteen
```

You can execute the instruction file via the command line like so:

```
./vendor/bin/wpinstructions run
```

Every instructions contains an `action` and a list of `options`. In the instructions file above, `install wordpress` is the action for the first command where `url` is an option. Each instruction supports different options.

## Supported Instructions

`install wordpress` 

__Options:__ *[version] [site title] [site url] [home url] [admin email] [admin user] [admin password] [install type=(multisite|single)] [path=(path to WP directory)]*

Installs WordPress. When using this instruction, you will need to pass the following parameters in via the command line: `--config_db_host`, `--config_db_name`, `--config_db_user`, `--config_db_pass`. For example:

```
./vendor/bin/wpinstructions run --config_db_host=localhost --config_db_name=wordpress --config_db_user=wordpress --config_db_pass=password
```

`install theme`

__Options__: *[theme name=(theme slug)] [theme version] [theme url] [theme status=(enabled|disabled)*

Installs a WordPress theme. `theme url` should be used for installing a theme via zip or git. `theme name` should match the theme slug.

`install plugin`

__Options__: *[plugin name=(plugin slug)] [plugin version] [plugin url] [plugin status=(active|network active|inactive)*

Installs a WordPress plugin. `plugin url` should be used for installing a plugin via zip or git. `plugin name` should match the plugin slug.

`add site`

__Options__: *[site title] [site url] [home url] [admin email] [admin user] [admin password]*

Add a site to a multisite instance. `site_url` and `home_url` are required as options.

`activate plugin`

__Options__: *[plugin name=(plugin slug)] [active type=(network|single)]

Activate a plugin.

`enable theme`

__Options__: *[theme name=(theme slug)]

Enable a theme.

## Examples

See examples in [EXAMPLES.md](EXAMPLES.md).

## Like what you see?

<a href="http://10up.com/contact/"><img src="https://10up.com/uploads/2016/10/10up-Github-Banner.png" width="850"></a>
