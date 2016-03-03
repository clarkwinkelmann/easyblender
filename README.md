# Easy Blender

This is a little php application that allows you to control video renders on Blender on a distant host.

I wrote this a long time ago but I still think that parts of the code can be relevant.

# Setup

- You should untar the Blender binaries in the `/blender` folder
- Create a MySQL database and run the `database.sql` script to create the tables
- Copy `config.example.php` to `config.php` and fill in your details
  - The `SECRET_KEY` is a string that the user will need to append to the admin url (`http://example.com/?k=TheSecretKey`) in order to access the api (Just a quick and dirty auth mechanism)
- Configure the web server of your choice to serve the root folder of the app (or just use `php -S localhost:8000` locally)
- It is ready !

# What it does

The script basically starts headless Blender renders in the background and redirects its output to a text file.
Another part of the script reads those text files to output the render status.

Built in is a sort of file manager which allows you to remotely upload blender files, launch renders, then download the output back.

# Support

This project is not maintained. But feel free to open an Issue if you have something to say about it ! I'll have a look ;-)

# License

This project is released under the MIT license.
