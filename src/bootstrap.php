<?php
# check for php version
if (version_compare('5.6.0', PHP_VERSION, '>')) {
    fwrite(
        STDERR,
        'PHP verion >= 5.6 is needed to run this application.' . PHP_EOL
    );

    exit(1);
}

# check timezone
if (!ini_get('date.timezone')) {
    ini_set('date.timezone', 'UTC');
}

# find composer's autoload.php file
$loader = null;
foreach ([__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../../autoload.php'] as $file) {
    if (file_exists($file)) {
        $loader = $file;
        break;
    }
}

# check if project is not set up yet
if (is_null($loader)) {
    fwrite(STDERR,
        'You need to set up the project dependencies using the following commands:' . PHP_EOL .
        'wget http://getcomposer.org/composer.phar' . PHP_EOL .
        'php composer.phar install' . PHP_EOL
    );

    exit(1);
}

# include composer autoload file
return include $loader;
