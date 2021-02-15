<?php
include 'includes' . DIRECTORY_SEPARATOR . 'HeaderWalkerNavMenu.php';

function AddStyles()
{
    $path = get_template_directory_uri();
    wp_enqueue_style('bootstrap-css', $path . '/css/bootstrap.min.css');
    wp_enqueue_style('fa-css', $path . '/css/font-awesome.min.css');
    wp_enqueue_style('style-css', $path . '/css/style.css');
}

function AddScripts()
{
    $path = get_template_directory_uri();
    wp_enqueue_script('jquery-js', $path . '/js/jquery.js');
    wp_enqueue_script('bootstrap-js', $path . '/js/bootstrap.min.js');
    wp_enqueue_script('respond-js', $path . '/js/respond.min.js');
    wp_enqueue_script('html5shiv-js', $path . '/js/html5shiv.js');
    wp_enqueue_script('custom-js', $path . '/js/custom.js');
}

add_action('wp_enqueue_scripts', 'AddStyles');
add_action('wp_footer', 'AddScripts');


// Menu
function RegisterMenu()
{
    register_nav_menu('header-menu', 'Header');
    register_nav_menu('footer-menu', 'Footer');
}

add_action('init','RegisterMenu');