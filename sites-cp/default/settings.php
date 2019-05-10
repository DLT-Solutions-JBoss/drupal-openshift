<?php

// Config sync directory.
$config_directories['sync'] = $app_root . '/' . $site_path . '/config/sync';

// Hash salt.
$settings['hash_salt'] = getenv('DRUPAL_HASH_SALT');

// Disallow access to update.php by anonymous users.
$settings['update_free_access'] = FALSE;

// Other helpful settings.
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

// Database connection.
$databases['default']['default'] = [
  'database' => getenv('DRUPAL_DATABASE_NAME'),
  'username' => getenv('DRUPAL_DATABASE_USER'),
  'password' => getenv('DRUPAL_DATABASE_PASSWORD'),
  'prefix' => '',
  'host' => getenv('DRUPAL_DATABASE_HOST'),
  'port' => getenv('DRUPAL_DATABASE_PORT'),
  'namespace' => 'Drupal\Core\Database\Driver\mysql',
  'driver' => 'mysql',
];
