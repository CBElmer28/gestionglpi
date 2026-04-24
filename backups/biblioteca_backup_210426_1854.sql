-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-04-2026 a las 01:53:11
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `biblioteca`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `glpi_id` int(11) DEFAULT NULL COMMENT 'ID en el contenedor Docker',
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `isbn` varchar(50) NOT NULL,
  `genre_id` bigint(20) UNSIGNED DEFAULT NULL,
  `publisher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `edition` varchar(100) DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Disponible',
  `synopsis` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `books`
--

INSERT INTO `books` (`id`, `glpi_id`, `title`, `author`, `isbn`, `genre_id`, `publisher_id`, `edition`, `genre`, `publisher`, `status`, `synopsis`, `created_at`) VALUES
(1, 1, 'El Psicoanalista', 'John Katzenbach', '978-8466615853', 6, 11, '2002 (Primera edición)', 'Suspenso (Thriller)', 'Ediciones B', 'Mantenimiento', 'El Dr. Frederick Starks, un psicoanalista con una vida tranquila, recibe un mensaje anónimo: \"Feliz cumpleaños, doctor. Bienvenido al primer día de su muerte\". Tiene 15 días para descubrir quién es el autor de la amenaza. Si no lo logra, deberá elegir entre suicidarse o ver cómo mueren sus seres queridos uno a uno. Un juego psicológico de vida o muerte donde nada es lo que parece.', '2026-04-15 21:01:56'),
(2, 2, 'Harry Potter y la piedra filosofal', 'J.K. Rowling', '978-8478884452', 3, 8, '1997', 'Fantasía', 'Salamandra', 'Mantenimiento', 'Harry Potter vive con sus horribles tíos hasta que, en su undécimo cumpleaños, descubre que es un mago y que ha sido invitado a asistir al Colegio Hogwarts de Magia y Hechicería. Allí no solo aprenderá hechizos y conocerá a sus mejores amigos, sino que descubrirá la verdad sobre su pasado y se enfrentará al mago oscuro más temido de todos los tiempos.', '2026-04-15 23:11:07'),
(3, 3, 'Fahrenheit 451', 'Ray Bradbury', '978-8497593120', 3, 8, '1953', NULL, NULL, 'Mantenimiento', 'En un futuro donde los libros están prohibidos, Guy Montag es un bombero cuya misión no es apagar incendios, sino quemar libros. La sociedad vive anestesiada por pantallas gigantes y entretenimiento vacío, hasta que Montag conoce a una joven que lo hace cuestionarse: ¿Por qué le tenemos tanto miedo a lo que dicen los libros? Una crítica poderosa a la censura y al conformismo.', '2026-04-15 23:24:06'),
(4, 4, 'Cien años de soledad', 'Gabriel García Márquez', '978-8420471839', 8, 6, '1967', NULL, NULL, 'Mantenimiento', 'La novela narra la historia de la familia Buendía a lo largo de siete generaciones en el pueblo ficticio de Macondo. Entre guerras civiles, inventos alquímicos y eventos sobrenaturales que todos aceptan como normales, la familia lucha contra una maldición de soledad que parece perseguirlos por siempre. Es considerada una de las obras más importantes de la lengua castellana.', '2026-04-15 23:37:03'),
(5, 5, 'Diez negritos', 'Agatha Christie', '978-8467045390', 5, 3, '1939', NULL, NULL, 'Mantenimiento', 'Diez personas que no se conocen entre sí son invitadas a una mansión en una isla privada. Durante la cena, una grabación los acusa a todos de haber cometido crímenes en el pasado. Atrapados por una tormenta, los invitados empiezan a morir uno a uno siguiendo las estrofas de una vieja canción infantil. El asesino está entre ellos, y el tiempo se agota.', '2026-04-16 00:33:53'),
(6, 6, 'Libro E2E 1776294488228', 'Autor Test', '9781234567890', 9, 6, '1939', NULL, NULL, 'Disponible', 'Esta es una sinopsis de prueba para E2E.', '2026-04-16 04:08:14'),
(7, 7, 'E2E Book 1776295580707', 'Playwright Author', '9781112223334', 10, 18, '1967', NULL, NULL, 'Prestado', 'Descripción de prueba.', '2026-04-16 04:26:22'),
(8, 8, 'E2E Book 1776296666966', 'Playwright Author', '978-1935211815', 2, 18, '1953', NULL, NULL, 'Disponible', 'Descripción de prueba.', '2026-04-16 04:44:29'),
(9, 9, 'E2E Book 1776297195058', 'Playwright Author', '978-9863433672', 2, 18, '1997', NULL, NULL, 'Disponible', 'Descripción de prueba.', '2026-04-16 04:53:18'),
(10, 10, 'E2E Book 1776297416874', 'Playwright Author', '978-2768423630', 2, 18, '2002 (Primera edición)', NULL, NULL, 'Disponible', 'Descripción de prueba.', '2026-04-16 04:57:06'),
(11, 11, 'El marciano', 'Andy Weir', '978-8466655057', 4, 11, '2011', NULL, NULL, 'Disponible', 'Tras una tormenta de arena en Marte, el astronauta Mark Watney es dado por muerto y abandonado por su tripulación. Pero está vivo. Con sus conocimientos de ingeniería y botánica, Watney deberá \"usar la ciencia\" para sobrevivir en un entorno hostil y lograr comunicarse con la Tierra. Una historia de ingenio, resolución de problemas y resistencia humana.', '2026-04-16 04:58:40'),
(12, 12, 'E2E Book 1776297992899', 'Playwright Author', '978-3657699436', 2, 18, '1939', NULL, NULL, 'Disponible', 'Descripción de prueba.', '2026-04-16 05:06:34'),
(17, 13, 'E2E Book 1776699527068', 'Playwright Author', '978-975423219', NULL, NULL, '1939', NULL, NULL, 'Disponible', 'Descripción de prueba.', '2026-04-21 23:49:28'),
(18, 14, 'E2E Book 1776699797516', 'Playwright Author', '978-578062075', 2, 18, '2019', NULL, NULL, 'Disponible', 'Descripción de prueba.', '2026-04-21 23:49:28'),
(19, 17, 'E2E Book 1776700304325', 'Playwright Author', '978-5483078991', 2, 18, '', NULL, NULL, 'Disponible', 'Descripción de prueba.', '2026-04-21 23:49:28'),
(20, 32, 'O\'Reilly Media', 'Shaun Wilkinson', '978-123', NULL, NULL, '1st', NULL, NULL, 'Disponible', 'Pruebas', '2026-04-21 23:49:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `genres`
--

CREATE TABLE `genres` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `glpi_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `genres`
--

INSERT INTO `genres` (`id`, `name`, `glpi_id`, `created_at`, `updated_at`) VALUES
(1, 'Terror', 1, '2026-04-15 21:47:53', '2026-04-15 21:47:53'),
(2, 'Acción y Aventuras', 2, '2026-04-15 21:47:53', '2026-04-15 21:47:53'),
(3, 'Fantasía', 3, '2026-04-15 21:47:53', '2026-04-15 21:47:53'),
(4, 'Ciencia Ficción', 4, '2026-04-15 21:47:53', '2026-04-15 21:47:53'),
(5, 'Policial', 5, '2026-04-15 21:47:53', '2026-04-15 21:47:53'),
(6, 'Suspenso (Thriller)', 6, '2026-04-15 21:47:53', '2026-04-15 21:47:53'),
(7, 'Romance', 7, '2026-04-15 21:47:53', '2026-04-15 21:47:53'),
(8, 'Drama / Realismo', 8, '2026-04-15 21:47:53', '2026-04-15 21:47:53'),
(9, 'Biografías y Autoayuda', 9, '2026-04-15 21:47:53', '2026-04-15 21:47:53'),
(10, 'Comedia', 10, '2026-04-15 21:47:53', '2026-04-15 21:47:53'),
(11, 'Realismo Mágico', 11, '2026-04-15 21:47:53', '2026-04-15 21:47:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_name` varchar(255) NOT NULL COMMENT 'Nombre del alumno/usuario',
  `loan_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('Activo','Devuelto','Atrasado') DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `loans`
--

INSERT INTO `loans` (`id`, `book_id`, `user_id`, `user_name`, `loan_date`, `return_date`, `status`) VALUES
(1, 7, NULL, 'Juanito Perez', '2026-04-17', '2026-04-20', 'Devuelto'),
(2, 7, NULL, 'Juanito Perez', '2026-04-20', '2026-04-27', 'Activo'),
(3, 16, NULL, 'Juanito Perez', '2026-04-20', '2026-04-27', 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 2),
(3, '2019_12_14_000001_create_personal_access_tokens_table', 3),
(4, '2019_08_19_000000_create_failed_jobs_table', 4),
(5, '2026_04_15_163319_create_genres_table', 4),
(6, '2026_04_15_163320_create_publishers_table', 4),
(7, '2026_04_15_163321_add_master_keys_to_books_table', 4),
(8, '2026_04_15_173329_create_reports_table', 5),
(10, '2026_04_15_175035_add_user_id_to_reports_table', 6),
(11, '2026_04_15_234801_drop_categories_table', 7),
(12, '2026_04_17_190008_add_user_id_to_loans_table', 8),
(13, '2026_04_17_200000_create_permission_tables', 9),
(14, '2026_04_17_210000_drop_legacy_role_column', 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'Ver Libros', 'books.view', '2026-04-18 02:26:29', '2026-04-18 02:26:29'),
(2, 'Gestionar Libros', 'books.manage', '2026-04-18 02:26:29', '2026-04-18 02:26:29'),
(3, 'Gestionar Catálogo', 'catalog.manage', '2026-04-18 02:26:29', '2026-04-18 02:26:29'),
(4, 'Ver Todos los Préstamos', 'loans.view_all', '2026-04-18 02:26:29', '2026-04-18 02:26:29'),
(5, 'Ver Mis Préstamos', 'loans.view_own', '2026-04-18 02:26:29', '2026-04-18 02:26:29'),
(6, 'Gestionar Préstamos', 'loans.manage', '2026-04-18 02:26:29', '2026-04-18 02:26:29'),
(7, 'Reportar Incidencia', 'incidents.report', '2026-04-18 02:26:29', '2026-04-18 02:26:29'),
(8, 'Gestionar Usuarios', 'users.manage', '2026-04-18 02:26:29', '2026-04-18 02:26:29'),
(9, 'Gestionar GLPI', 'glpi.manage', '2026-04-18 02:26:29', '2026-04-18 02:26:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permission_role`
--

CREATE TABLE `permission_role` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permission_role`
--

INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(2, 1),
(2, 2),
(3, 1),
(3, 2),
(4, 1),
(4, 2),
(5, 1),
(5, 2),
(5, 3),
(6, 1),
(6, 2),
(7, 1),
(7, 2),
(7, 3),
(8, 1),
(9, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(159, 'App\\Models\\User', 2, 'biblioteca-token', 'bbc87782283c5526c69c123a131a2037363022cf644ab5cc410b6ab494d4cb02', '[\"books.view\",\"books.manage\",\"catalog.manage\",\"loans.view_all\",\"loans.view_own\",\"loans.manage\",\"incidents.report\"]', '2026-04-20 20:52:04', NULL, '2026-04-20 20:51:59', '2026-04-20 20:52:04'),
(173, 'App\\Models\\User', 1, 'biblioteca-token', 'ce94f335139730bc517cdf84992e5c46da5c8f952f1c1c11ea67068d3a44e945', '[\"books.view\",\"books.manage\",\"catalog.manage\",\"loans.view_all\",\"loans.view_own\",\"loans.manage\",\"incidents.report\",\"users.manage\",\"glpi.manage\"]', '2026-04-21 23:52:47', NULL, '2026-04-21 23:42:30', '2026-04-21 23:52:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publishers`
--

CREATE TABLE `publishers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `glpi_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `publishers`
--

INSERT INTO `publishers` (`id`, `name`, `glpi_id`, `created_at`, `updated_at`) VALUES
(1, 'Planeta', 1, '2026-04-15 21:47:55', '2026-04-15 21:47:55'),
(2, 'Seix Barral', 2, '2026-04-15 21:47:55', '2026-04-15 21:47:55'),
(3, 'Espasa', 3, '2026-04-15 21:47:55', '2026-04-15 21:47:55'),
(4, 'Minotauro', 4, '2026-04-15 21:47:55', '2026-04-15 21:47:55'),
(5, 'Destino', 5, '2026-04-15 21:47:55', '2026-04-15 21:47:55'),
(6, 'Alfaguara', 6, '2026-04-15 21:47:55', '2026-04-15 21:47:55'),
(7, 'Penguin', 7, '2026-04-15 21:47:55', '2026-04-15 21:47:55'),
(8, 'Salamandra', 8, '2026-04-15 21:47:55', '2026-04-15 21:47:55'),
(9, 'Debolsillo', 9, '2026-04-15 21:47:55', '2026-04-15 21:47:55'),
(10, 'Lumen', 10, '2026-04-15 21:47:55', '2026-04-15 21:47:55'),
(11, 'Ediciones B', 11, '2026-04-15 21:47:55', '2026-04-15 21:47:55'),
(12, 'Anaya', 12, '2026-04-15 21:47:55', '2026-04-15 21:47:55'),
(13, 'Algaida', 13, '2026-04-15 21:47:55', '2026-04-15 21:47:55'),
(14, 'Anagrama', 14, '2026-04-15 21:47:55', '2026-04-15 21:47:55'),
(15, 'Tusquets', 15, '2026-04-15 21:47:55', '2026-04-15 21:47:55'),
(16, 'Akal', 16, '2026-04-15 21:47:56', '2026-04-15 21:47:56'),
(17, 'Siruela', 17, '2026-04-15 21:47:56', '2026-04-15 21:47:56'),
(18, 'Acantilado', 18, '2026-04-15 21:47:56', '2026-04-15 21:47:56'),
(19, 'Blackie Books', 19, '2026-04-15 21:47:56', '2026-04-15 21:47:56'),
(20, 'Impedimenta', 20, '2026-04-15 21:47:56', '2026-04-15 21:47:56'),
(21, 'Siglo XXI', 21, '2026-04-15 21:47:56', '2026-04-15 21:47:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reports`
--

CREATE TABLE `reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `book_id` int(11) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `glpi_ticket_id` varchar(255) DEFAULT NULL,
  `priority` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reports`
--

INSERT INTO `reports` (`id`, `book_id`, `user_id`, `glpi_ticket_id`, `priority`, `description`, `image_path`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '1', 'Alta', 'El libro tiene la portada dañada', 'reports/ms42XumYwJXeCWjlyiq9py8Z8zS9El8gGJ1MUtcW.png', '2026-04-15 23:02:46', '2026-04-15 23:02:48'),
(2, 2, 1, '2', 'Baja', 'El libro contiene rayones minimos en la cubierta', 'reports/vkV7zNIgjjYKfINQogfG5mWGg3UZghb4dbJX6f6L.png', '2026-04-15 23:13:22', '2026-04-15 23:13:23'),
(3, 3, 1, '3', 'Media', 'El libro tiene hojas sueltas', 'reports/lxFDfpReF67RNMhtsj7aOzu5GHcE8JkavtYQ8lH0.png', '2026-04-15 23:55:39', '2026-04-15 23:55:41'),
(4, 4, 3, '4', 'Alta', 'Le faltan paginas', 'reports/8AltPSvmIzk2e8LfjjtyOYkhitdGUA0jjaxZ0T6w.jpg', '2026-04-16 00:19:14', '2026-04-16 00:19:16'),
(5, 5, 3, '5', 'Baja', 'El libro contiene algunas hojas deterioradas', NULL, '2026-04-16 00:34:34', '2026-04-16 00:34:35'),
(6, 7, 3, '6', 'Media', 'El libro está dañado', NULL, '2026-04-20 21:41:51', '2026-04-20 21:41:58'),
(7, 11, 3, '7', 'Media', 'Prueba de reporte', NULL, '2026-04-20 22:17:19', '2026-04-20 22:17:24'),
(8, 13, 3, '8', 'Media', 'Prueba de incidencia', NULL, '2026-04-20 22:30:25', '2026-04-20 22:30:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'admin', '2026-04-18 02:26:29', '2026-04-18 02:26:29'),
(2, 'Bibliotecario', 'bibliotecario', '2026-04-18 02:26:29', '2026-04-18 02:26:29'),
(3, 'Lector', 'lector', '2026-04-18 02:26:29', '2026-04-18 02:26:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `glpi_user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID del usuario en GLPI',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `glpi_user_id`, `remember_token`, `created_at`, `updated_at`, `role_id`) VALUES
(1, 'Administrador', 'admin@biblioteca.com', NULL, '$2y$12$ltk/J0eM6ZztLU500apakOFyfgQ4HMaQvF1bO9JRXZqGuod9X8D0u', NULL, NULL, '2026-04-15 03:42:14', '2026-04-18 02:26:29', 1),
(2, 'Bibliotecario', 'bibliotecario@biblioteca.com', NULL, '$2y$12$nTTMgKwHzwyrNgrZb8uMMujKIVSYK8uNSLmsa5lSc9p47TVXsxXL6', NULL, NULL, '2026-04-15 03:42:15', '2026-04-18 02:26:29', 2),
(3, 'Louise Matinent', 'louisegimenez@biblioteca.com', NULL, '$2y$12$ltk/J0eM6ZztLU500apakOFyfgQ4HMaQvF1bO9JRXZqGuod9X8D0u', 8, NULL, '2026-04-16 00:18:16', '2026-04-18 02:26:29', 2),
(4, 'Juanito Perez', 'juanito@gmail.com', NULL, '$2y$12$hXExnKoimf7kanIn2NC0x.IblTbI7vkYxXYVSpO226X9KeXUdVWmy', NULL, NULL, '2026-04-17 23:49:50', '2026-04-18 02:26:29', 3);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn` (`isbn`),
  ADD KEY `books_genre_id_foreign` (`genre_id`),
  ADD KEY `books_publisher_id_foreign` (`publisher_id`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indices de la tabla `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `genres_glpi_id_unique` (`glpi_id`);

--
-- Indices de la tabla `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `loans_user_id_foreign` (`user_id`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indices de la tabla `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_slug_unique` (`slug`);

--
-- Indices de la tabla `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `permission_role_role_id_foreign` (`role_id`);

--
-- Indices de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indices de la tabla `publishers`
--
ALTER TABLE `publishers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `publishers_glpi_id_unique` (`glpi_id`);

--
-- Indices de la tabla `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reports_book_id_foreign` (`book_id`),
  ADD KEY `reports_user_id_foreign` (`user_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_slug_unique` (`slug`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_id_foreign` (`role_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `genres`
--
ALTER TABLE `genres`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=174;

--
-- AUTO_INCREMENT de la tabla `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `reports`
--
ALTER TABLE `reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_genre_id_foreign` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `books_publisher_id_foreign` FOREIGN KEY (`publisher_id`) REFERENCES `publishers` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
