<?php
# Find composer's autoload.php file
$loader = null;
foreach ([__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../../autoload.php'] as $file) {
    if (file_exists($file)) {
        $loader = $file;
        break;
    }
}

# Check if project is not set up yet
if (is_null($loader)) {
    fwrite(STDERR,
        'You need to set up the project dependencies using the following commands:' . PHP_EOL .
        'wget http://getcomposer.org/composer.phar' . PHP_EOL .
        'php composer.phar install' . PHP_EOL
    );

    exit(1);
}

# Include composer autoload file
return include $loader;
