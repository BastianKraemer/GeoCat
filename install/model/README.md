# GeoCat database model
## WWW SQL Designer

The GeoCat database model has been created using the WWW SQL Designer (https://github.com/ondras/wwwsqldesigner/).

You can use the data from the `geocatdb_model.xml` file to display the model in your browser.

## Updating the GeoCat SQL setup data

If you want to update the GeoCat database model you have to follow these steps:

1. Save the new XML-Data in the `geocatdb_model.xml` file
1. Export the new SQL code for MySQL and PostgreSQL and store them in the `mysql.setup.sql` and `pgsql.setup.sql` file
1. Run the `updateSQLFiles.sh` script which is located in the `install/lib` directory
1. Update the database version information ("DB_VERSION") and increment the revision ("DB_REVISION") in `src/app/GeoCat.php`
