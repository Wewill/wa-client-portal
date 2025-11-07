<?php
/**
 * Register Meta Box fields for user profile/registration
 */

global $current_edition_id;

add_filter( 'rwmb_meta_boxes', function( $meta_boxes ) {
	$prefix = 'wacp-';

	$meta_boxes[] = [
		'id'    => 'wa_client_portal_user_fields',
		'title' => 'Informations Client',
		'type'  => 'user',
		'fields' => [
			// All films that have current_edition_id 
			[
                'name'            => __( 'My favorites films', 'wacp' ),
                'id'              => $prefix . 'favorite_films',
                'type'            => 'post',
                'post_type'       => ['film'],
                'add_new'         => false,
                'multiple'        => true,
                'parent'          => false,
				'query_args'	  => [
					'meta_query' => [
						[
							'key'     => '_status',
							'value' => ['approved','programmed'],
							'compare' => 'IN',
						],
					],
				],
                // 'query_args'      => [
                //     'tax_query' => [
                //         [
                //             'taxonomy' => 'edition',
				//             'field'    => 'id',
				//             'terms'    => $current_edition_id,
				//             'operator' => 'IN',
                //         ],
                //     ],
                // ],
                'hide_from_rest'  => false,
            ],
		],
	];
	return $meta_boxes;
});


/**
 * Force Meta Box to save wacp-favorite_films as a single serialized array
 * instead of multiple rows.
 */
add_action( 'rwmb_wacp-favorite-films_before_save', function( $new, $field, $old, $object_id ) {

    // Make sure we’re dealing with a user field
    if ( 'user' === $field['object_type'] ) {
        // Normalize to array
        $new = (array) $new;

        // Save as a single entry (WordPress will serialize automatically)
        update_user_meta( $object_id, $field['id'], $new );

        // Returning false tells Meta Box not to perform its default save
        return false;
    }

    return $new;
}, 10, 4 );