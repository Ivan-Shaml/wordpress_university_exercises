<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress_upr' );

/** MySQL database username */
define( 'DB_USER', 'wordpress_upr' );

/** MySQL database password */
define( 'DB_PASSWORD', 'wordpress_upr1' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '*gicFPG29H43}nv(/D5MgclPPR?]`0Qty 25(M<X0Z)F%w-@cDth%(~P(;h+L;E@' );
define( 'SECURE_AUTH_KEY',  '~9OVs6Jq$]I]>s~e/uov]qpb{@5V,uAJ%5W{b3GEUSDS)~(QLconr>T+G8}d{o 5' );
define( 'LOGGED_IN_KEY',    '9B&_=8q@~Q37<kwKWaw]PI$b mpbcpS].0T5~<YU!qwJPE6UIex0nq@m+W@a$Zc3' );
define( 'NONCE_KEY',        'c$6r;~#A ccv:/xb(/?EyB!zhr~o{J$+Fu> AX{4IUJD:b+Be;/vl{Vt{]$-`nbA' );
define( 'AUTH_SALT',        'Ui57!r-f;9~K$aX7o:^-vek;Ao53e,@BSR$F;/71}{}v_^iciq(mv.+8OCJi.2ch' );
define( 'SECURE_AUTH_SALT', '(JN]Z?.hB]dV js]6mLS~ZA{!4=s.Z=j3QAa^Dgq+zlh>0pl][=jrK2GW$)`cD!f' );
define( 'LOGGED_IN_SALT',   'G#OBZV}cMv.VxX!VpU_H9hqGx86H!-XebBO-foS0B^Y|[@~?t456cGQC.VF+IAbP' );
define( 'NONCE_SALT',       '4+ ^7yh::e<w73p+CMM38sV6ltmAv%X$Im2m!R!],q@fLhU5<_xee!`ewrIt+Mg&' );

/**#@-*/

/**
 * WordPress Database Table prefix.
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

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
