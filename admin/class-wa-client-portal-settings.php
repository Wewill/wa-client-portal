<?php
/*
Define admin settings
*/
add_filter( 'mb_settings_pages', 'wacp_settings');
function wacp_settings( $settings_pages ) {
	$settings_pages[] = [
        'menu_title'      => __( 'Portal settings', 'wacp' ),
        'id'              => 'portal-settings',
        'parent'          => 'options-general.php',
        'class'           => 'wacp',
		'style'      => 'no-boxes',
        // 'tabs'            => [
        //     'edition'  => 'Edition',
        //     'archives' => 'Archives',
        //     'template' => 'Template',
        // ],
        // 'tab_style'       => 'left',
        // 'help_tabs'       => [
        //     [
        //         'title'   => 'Help me !',
        //         'content' => 'Lorem ipsum...',
        //     ],
        // ],
        'columns'         => 1,
        'customizer'      => false,
        'customizer_only' => false,
        'network'         => false,
        'icon_url'        => 'dashicons-filter',
    ];

	return $settings_pages;
}

add_filter( 'rwmb_meta_boxes', 'wacp_settings_fields', 50);
function wacp_settings_fields( $meta_boxes ) {
    $prefix = 'wacp_';

    // Adds a recaptcha Site key and secret key fields
    $meta_boxes[] = [
        'title'      => __( 'Google reCAPTCHA V2 settings', 'wacp' ),
        'id'         => 'wacp_recaptcha_settings',
        'settings_pages' => ['portal-settings'],
        'fields'     => [
            // Dsiplay an info text with the recaptcha website url to get keys
            [
                'id'   => $prefix . 'recaptcha_info',
                'type' => 'heading',
                'name' => __( 'Google reCAPTCHA V2 settings', 'wacp' ),
                'desc' => sprintf( __( 'To utilize the Google reCAPTCHA service on your website you need to get a Site and Secret key.<br/> If you do not have these keys yet, you can get them for free by registering to <a href="%s" target="_blank">the Google reCAPTCHA admin</a> to get your keys.', 'wacp' ), 'https://www.google.com/recaptcha/admin/' ),
            ],
            [
                'type' => 'divider',
            ],
            [
                'id'   => $prefix . 'recaptcha_site_key',
                'type' => 'text',
                'name' => __( 'reCAPTCHA Site Key', 'wacp' ),
                'desc' => __( 'Enter your reCAPTCHA Site Key', 'wacp' ),          
            ],
            [
                'id'   => $prefix . 'recaptcha_secret_key',
                'type' => 'text',
                'name' => __( 'reCAPTCHA Secret Key', 'wacp' ),
                'desc' => __( 'Enter your reCAPTCHA Secret Key', 'wacp' ),
            ],
        ]
    ];  

            
    return $meta_boxes;
}

function wacp_get_recaptcha_site_key_from_setting_page() {
    $prefix = 'wacp_';
    return rwmb_meta( $prefix . 'recaptcha_site_key', [ 'object_type' => 'setting' ], 'portal-settings' );
}


function wacp_get_recaptcha_secret_key_from_setting_page() {
    $prefix = 'wacp_';
    return rwmb_meta( $prefix . 'recaptcha_secret_key', [ 'object_type' => 'setting' ], 'portal-settings' );
}
