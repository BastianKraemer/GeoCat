# Installing GeoCat

## Regular usage

You have to perform three steps

1. Create a GeoCat config file named 'config.php' in the directory 'src/config/' (you can use the sample configuration 'src/config/config.php' as template)
1. Detup teh database using the GeoCat setup script in: `install/geocat.php --install`
1. Run GeoCat build using 'Grunt': `grunt build`

> Maybe you have to setup the build environment before. Therefore you have to install 'Node.js' and 'npm'
> After this you can run 'npm install' to download all build depedencies

## Using Docker

You can use GeoCat in a Docker container.

Therefore you have to run the following commands:

```
docker build -t geocat .
docker run -d --expose 80 -p 80:80 --name geocat-run geocat
```

By default the docker container will use the files in the 'dest/' folder of GeoCat.
So you have to run `grunt build` before creating the docker container.

> If you want to include custom files like an imprint or a data privacy statement you can place them in the "docker_include" directory.

If you want to launch a bash inside your container you can use the following command:

`docker exec -it geocat-run bash`

## Using a MariaDB docker container
### Create a mariadb Dockerfile

At first you should create a dockerfile for MariaDB, you can modify the 'ENV' variables the way you want:

```
FROM mariadb:latest

ENV MYSQL_ROOT_PASSWORD root
ENV MYSQL_DATABASE geocat
ENV MYSQL_USER geocat
ENV MYSQL_PASSWORD geocatpw
```

### Create the container

```
docker build -t geocat-mariadb .
docker run -d --expose 3306 -p 3306:3306 --name geocat-mariadb-run geocat-mariadb
```

### Update GeoCat

Now your database is up an running. Now you can update the `config.php` file according to the new database host and run the GeoCat setup script to prepare the database.

> There are a lot of other possibilities to setup a MariaDB docker container.
> For example you can use the GeoCat sql files to setup the database in your container automatically on startup.
> For more information take a look at the MariaDB docker page.
