# GeoCat

GeoCat is a HTML5 Geocaching and -tracking platform. The backend is written in PHP.

Note: Currently GeoCat has been tested with PHP 5.6 only.

## Getting started

### Environment

- Webserver like Apache or nginx
- PHP Environment with enabled "mcrypt" module
- MariaDB/MySQL or PostgreSQL database

### Grunt

This step is optional and minifies some JavaScript and CSS files, it's also possible to use the _src/_ files directly.

GeoCat is built by **Grunt** (http://gruntjs.com).
Therefore you have to install **Nodejs** (https://nodejs.org) and **npm** (https://www.npmjs.com)

After this you can install all dependencies using npm:

```
cd [path/to/geocat]
npm install
```

Last but not least, run Grunt to build GeoCat

```
grunt build
```

You will find the new files in the "dest" folder.

If you want to generate php- or JavaScript documentation simply run "grunt doc".

### Database setup

The geocat database can be set up by the php script "setup.php"

To create the database the first time you can use the following parameters:
```
cd [/path/to/geocat]/install
php setup.php --install --type [mysql|pgsql] --user [database_username] --pw [database_password] --create [database_name]
```

For more information use "php setup.php --help"

## License
GeoCat is release under GNU GPL v3 License
