# omdb

index.php does all the magic here, and loading that in the browser will generate the page each time, downloading any new cover images first.

If you want to set up a cronjob or something to update it every so often and then serve a static copy (which I recommend), you can essentially just do:

php index.php > index.html

Only issue is the paths are all local to the dir you stick this all in, so in your crontab you probably need to cd into that dir first.