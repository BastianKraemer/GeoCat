# GeoCat

GeoCat is a HTML5 Geocaching and -tracking platform. The backend is written in PHP.

Note: You will need at least PHP 5.6 or higher. Older version are not supported.

## Getting started

### Environment

- Webserver like Apache or nginx
- PHP 5.6 oder PHP 7 environment with enabled 'mcrypt' and 'pdo' module
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

You will find the new files in the 'dest' folder.

If you want to generate PHP- or JavaScript documentation simply run 'grunt doc'.

### Database setup

First of all you have to create the GeoCat configuration file called 'config.php' in the 'config' directory.
You will find a sample configuration in the 'sample_config.php' file.

After this you can set up the database by using the GeoCat command line interface:
```
cd [/path/to/geocat]/install
php geocat.php --install
```

For more information use 'php geocat.php --help'

### Docker

It is also possible to run GeoCat in a docker container. For more information take a look at the install README.

## License
GeoCat is release under GNU GPL v3 License
