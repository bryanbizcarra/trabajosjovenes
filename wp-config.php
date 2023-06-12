<?php
 // WP-Optimize Cache
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
define( 'DB_NAME', 'trabajosjovenes_new' );
/** Database username */
define( 'DB_USER', 'root' );
/** Database password */
define( 'DB_PASSWORD', '' );
/** Database hostname */
define( 'DB_HOST', 'localhost' );
/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );
/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );
define('WP_MEMORY_LIMIT', '1024M');
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
define( 'AUTH_KEY',         ':U,O.NK+*tU4B8rfM@P,SV;M^Ame@Na*>wBrR9ym#h+HCRcR5,@~JepYr4<PQ3T2' );
define( 'SECURE_AUTH_KEY',  '+DKYJX}SKI0-LE!UghL8xZ-D&BcHnX,InGp!0g1dyJ>G<h#7K{n&4lh:W<N,nD]*' );
define( 'LOGGED_IN_KEY',    'a:bTgBVw,,S,FShPBZZc5${!ZJich88Py%1a]06Uni4RNC2@1BRw1Js`~</LUby=' );
define( 'NONCE_KEY',        '|G$>V2.aSZ(HHGFQG~U~fs09q#X-iRRt=zSCt5|ACCYxK4^qUg5gCx#0Lsm@qzy{' );
define( 'AUTH_SALT',        'NxugApusz$+Y;IH3+SU]POUO){EBkM__=SgVg[lSAigZondT/#qTfaX@mn2hs:$X' );
define( 'SECURE_AUTH_SALT', 'Dv^kQ4>cN=0M!vSm,J>uBv=zOH).R5I7.38?~@`Px:zEs8h5mO6F|Itl}`x%j`):' );
define( 'LOGGED_IN_SALT',   '#CJl#s5ckI2>_b>lu8d_B<.,W[CLqMLZ MMPyl]EFzXguxa.QY#kTA={G!cPkzCF' );
define( 'NONCE_SALT',       'd:U:L?D92rx@w}()xJMY)S>rTWuX^;RN;9n`T;Oq:alMIf)4LgVBs!O*l>]eA?Bp' );
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
define( 'WP_DEBUG', false );
/* Add any custom values between this line and the "stop editing" line. */
/* That's all, stop editing! Happy publishing. */
/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';