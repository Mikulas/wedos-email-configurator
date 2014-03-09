Wedos Email configurator (WIP)
==============================

Automatic authentication is not implemented. Log in to Wedos client side and copy and paste all cookies to `./Wedos/Commands/.cookies`.

Create `config.neon` file according to example. Dump `$config->getCompiled()` to see final output.

Run `php run.php sync config.neon` to synchronize. Show settings by `php run.php list %id` where %id is your hosting id (get it in url).

Open `config.example_compiled.txt` to see what the example config compiles to.

Warning: wedos limits number of forwards, so larger groups wont work!
