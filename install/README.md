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

> If you want to include custom files like an imprint or a data privacy statement you can place them in the "docker_include" directory.
