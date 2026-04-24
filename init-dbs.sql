CREATE DATABASE IF NOT EXISTS `laravel_biblioteca`;
CREATE DATABASE IF NOT EXISTS `glpi_db`;

GRANT ALL PRIVILEGES ON `laravel_biblioteca`.* TO 'user_local'@'%';
GRANT ALL PRIVILEGES ON `glpi_db`.* TO 'user_local'@'%';

FLUSH PRIVILEGES;