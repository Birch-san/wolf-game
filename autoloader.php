<?php
spl_autoload_register( 'autoload' );

/**
 * autoload
 *
 * @author Joe Sexton <joe.sexton@bigideas.com>
 * @param  string $class
 * @param  string $dir
 * @return bool
 */
function autoload( $class, $dir = null ) {

  if ( is_null( $dir ) )
    $dir = './';

  foreach ( scandir( $dir ) as $file ) {

    // directory?
    if ( is_dir( $dir.$file ) && substr( $file, 0, 1 ) !== '.' )
      autoload( $class, $dir.$file.'/' );

    // php file?
    if ( substr( $file, 0, 2 ) !== '._' && preg_match( "/.php$/i" , $file ) ) {

      // filename matches class?
      if ( str_replace( '.php', '', $file ) == $class || str_replace( '.class.php', '', $file ) == $class ) {

        include $dir . $file;
      }
    }
  }
}
