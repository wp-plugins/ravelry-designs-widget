<?php
/**
 * @package Ravelry Designs Widget
 */
/*
Plugin Name: Ravelry Designs Widget
Plugin URI: http://codebyshellbot.com/wordpress-plugins/ravelry-designs-widget/
Description: Display your own knitting & crochet designs straight from Ravelry.
Version: 1.0.0
Author: Shellbot
Author URI: http://codebyshellbot.com
Text Domain: ravelry-designs-widget
*/

class sb_ravelry_designs_widget {

    function __construct() {
        define('PLUGIN_PATH', plugin_dir_path(__FILE__));
        define('RAVELRY_API_URL', 'http://api.ravelry.com');
        define('RAVELRY_BASE_URL', 'http://www.ravelry.com/patterns/library/');
        
        add_action( 'wp_print_styles', array( $this, 'rdw_add_styles' ) );
        add_shortcode( 'sb_ravelry_designs', array( $this, 'rdw_shortcode' ) );

        require_once PLUGIN_PATH . 'class-ravelry-designs-widget.php'; 
    }

    function rdw_add_styles() {
        $css = '<style type="text/css">'
            . '.rav-container { display: inline-block; position: relative; width: 100%; }'
            . '.rav-dummy { margin-top: 100%; }'
            . '.rav-element { position: absolute;top: 0;bottom: 0;left: 0;right: 0;}'
            . '.rav-element a.thing { display: block; height: 100%; }'
            . '.widget_ravelry_designs_widget ul, .widget_ravelry_designs_widget li { list-style-type: none !important; margin-left: 0 !important; }'
            . '.widget_ravelry_designs_widget .layout_1 li { margin-bottom: 5px; }'
            . '.widget_ravelry_designs_widget .layout_1 img { display: inline-block; margin-right: 5px; vertical-align: middle; }'
            . '.widget_ravelry_designs_widget .layout_2 .pattern-name { background: rgba(0,0,0,0.7); bottom: 0; display: block; margin-left: 0; padding: 10px 0; position: absolute; width: 100%; }'
            . '.widget_ravelry_designs_widget .layout_2  a {color: #fff !important; text-align: center; text-decoration: none;}'
            . '.widget_ravelry_designs_widget .cols-2 li { float: left; margin-bottom: 1%; margin-right: 2%; width: 49%; }'
            . '.widget_ravelry_designs_widget .cols-3 li { float: left; margin-bottom: 0.25%; margin-right: 1%; width: 32.333%; }'
            . '.widget_ravelry_designs_widget .cols-4 li { float: left; margin-bottom: 0.25%; margin-right: 1%; width: 24%; }'
            . '.widget_ravelry_designs_widget .cols-2 li:nth-child(2n), .widget_ravelry_designs_widget .cols-3 li:nth-child(3n), .widget_ravelry_designs_widget .cols-4 li:nth-child(4n) {  margin-right: 0 !important; }'
            . '</style>';

        echo $css;   
    }
    
    function rdw_shortcode( $args ) { 
        extract( shortcode_atts(array(
            'designer' => 'Michelle May',
            'layout' => 'layout_2',
            'show' => '3',
            'cols' => '3',
            'new_tab' => 'no',
        ), $args ) );
        
        $final_args = array();
        
        $final_args['rav_designer_name'] = $designer;
        $final_args['show_num'] = $show;
        $final_args['layout'] = $layout;
        $final_args['columns'] = $cols;
        $final_args['new_tab'] = $new_tab;
        
        ob_start();
        echo '<div class="widget_ravelry_designs_widget">';
        $this->show_patterns( $final_args );
        echo '</div>';
        ob_end_flush();
    }
    
    function show_patterns( $args ) {
        
        if( empty( $args['rav_designer_name'] ) ) {
            echo '<p>Valid Ravelry designer name required.</p>';
        } else {
            if ( false === ( $output = get_transient( md5( 'sbrdw'.serialize( $args ) ) ) ) ) {        

                $secret = 'CVBR21QepTC1Zwj0MEvHz+1rvmv285bH7XsF9tir'; // your secret key

                $data = array();

                $data['access_key'] = '7B78C7930DB53FE4C60D'; // your access key
                $data['designer'] = $args['rav_designer_name']; // the store search query for full text search
                $data['page_size'] = $args['show_num']; // for example
                $data['timestamp'] = date('c'); // gets the current date/time

                $string = RAVELRY_API_URL . '/patterns/search.json?' . http_build_query($data);

                $signature = base64_encode(hash_hmac('sha256', $string, $secret, true));

                $data['signature'] = $signature;


                $final = http_build_query($data);
                $final = RAVELRY_API_URL . '/patterns/search.json?' . $final;
                // Begin CURL section - getting the response from the URL that 
                // was built above.

                $ch = curl_init();
                // set URL and other appropriate options
                curl_setopt($ch, CURLOPT_URL, $final);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

                // grab URL and pass it to the browser
                $output = curl_exec($ch);

                // close cURL resource to free up system resources
                curl_close($ch);        

            }

            $data = json_decode($output); 

            $i = 1;

            $pattern_list = '<ul class="' . $args['layout'] . ' cols-' . $args['columns'] . '">';

            foreach( $data->patterns as $pattern ) {

                if( $i > $args['show_num'] ) {
                    continue;
                } 

                if( $args['new_tab'] == 'yes' ) {
                    $target = 'target="_blank"';
                } else {
                    $target = '';
                }

                if( $args['layout'] == 'layout_1' ) {
                    $photo = $pattern->first_photo->square_url;
                    $pattern_list .= '<li><a href="' . RAVELRY_BASE_URL . $pattern->permalink . '" ' . $target . '><img src="' . $photo .'" height="40" width="40">' . $pattern->name . '</a></li>';
                } else {
                    $photo = $pattern->first_photo->medium_url;
                    $pattern_list .= '<li><div class="rav-container">'
                            . '<div class="rav-dummy"></div>'
                            . '<div class="rav-element" style="background: url('.$photo.') no-repeat center center; background-size: cover;">'
                            . '<a class="thing" href="' . RAVELRY_BASE_URL . $pattern->permalink . '" ' . $target . '>'
                            . '<span class="pattern-name">' . $pattern->name . '</span>'
                            . '</a>'
                            . '</div>'
                            . '</div></li>';
                }          

                $i++;

            }

            $pattern_list .= '</ul>';

            echo $pattern_list;
            
            set_transient( md5( 'sbrdw'.serialize( $args ) ) , $output, 10 * 60);
        }
    }   

}

$sbrdw = new sb_ravelry_designs_widget();