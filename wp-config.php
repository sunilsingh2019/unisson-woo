<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'unissondisability-woo' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'Bang2021!' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '(]5:2Q6nCiSNLy0xV*nnzke7b=focw+<m|ANaHL]8jOX(eI<wOmYF<f;X56=Zt`e' );
define( 'SECURE_AUTH_KEY',  '=y}|^pCS)u]gKw/E&IUc>GOh{.wxW`r^-mykV]koY*W025{,)(55^4Fd%i,^VKw ' );
define( 'LOGGED_IN_KEY',    'H)`-FSL=kxg@Q~s07-bRSw-0G3kU^pQ?{g??a!7j qJ9%1f*adn~v_}X@Gu]6{R[' );
define( 'NONCE_KEY',        '1tYt5S^`aUIy7$*=<jKBHz4Uj6WJ,:|F<Xc)JvJr.<eq>36yS$0}LEe[s>y?J)z=' );
define( 'AUTH_SALT',        'oope9Y)R.k{v9~Asr206BT/3UA?/q~wZb+EKYX~au[7rEAi4I@1-tXMrjKKylxIa' );
define( 'SECURE_AUTH_SALT', 'uk936vonHot+hPZe.M|9a.rP9lg?;lldZt>*[W;Nf81:k{#~#@(KTSyU<@|uHW^~' );
define( 'LOGGED_IN_SALT',   'pm6 m!&Mx1S5Nm$vamEZ{X>BcEx+(btF.Pz7l`3T:`l5<[rPsZboG.jb_H5$Dd9x' );
define( 'NONCE_SALT',       ':BVbsx)t *$1jEP`M76]W-6[9}WlSf{eA~ZAGr6;_N@T]mqIH-(v}Ha*t< f ^lO' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
//define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */

// define( 'WP_DEBUG', true );
// define( 'WP_DEBUG_DISPLAY', true );
// define( 'WP_DEBUG_LOG', true );

/* That's all, stop editing! Happy publishing. */

define('CONCATENATE_SCRIPTS', false);


/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';