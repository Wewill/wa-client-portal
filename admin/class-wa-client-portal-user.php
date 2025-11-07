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
 * Choose the way of storing in database (serialize or json)
 */
add_filter( 'rwmb_wacp-favorite-films_value', function( $new, $old, $object_id ) {
    // Ensure it's saved as a single user_meta entry (array)
    if ( is_array( $new ) ) {
        return $new;
    }
    return (array) $new;
}, 10, 3 );
// Or 
// add_filter( 'rwmb_wacp-favorite-films_sanitize', function( $new, $field ) {
//     // Ensure it's stored as an array (WordPress handles serialization)
//     return (array) $new;
// }, 10, 2 );


//And when retrieving, you can unserialize it:
add_filter( 'rwmb_wacp-favorite-films_value', function( $value, $args, $object_id ) {
    if ( is_serialized( $value ) ) {
        return maybe_unserialize( $value );
    }
    return $value;
}, 10, 3 );