<?php
/**
 * Register Meta Box fields for user profile/registration
 */
add_filter( 'rwmb_meta_boxes', function( $meta_boxes ) {
	$prefix = 'wacp-';

	$meta_boxes[] = [
		'id'    => 'wa_client_portal_user_fields',
		'title' => 'Informations Client',
		'type'  => 'user',
		'fields' => [
			[
				'id'   => $prefix . 'entity',
				'type' => 'text',
				'name' => __( 'Entity', 'wacp' ),
			],
			[
				'id'   => $prefix . 'media',
				'type' => 'text',
				'name' => __( 'Media', 'wacp' ),
			],
			[
				'id'   => $prefix . 'phone',
				'type' => 'text',
				'name' => __( 'Phone', 'wacp' ),
			],
		],
	];
	return $meta_boxes;
});