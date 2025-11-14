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
                //'clone'           => true,
                'save_field'      => false, // Prevent metabox.io from saving each selection as separate meta
                //'sanitize_callback' => 'my_sanitize_favorite_films_field',

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
            // User notes
            [
                'name'           => __( 'My notes', 'wacp' ),
                'id'             => $prefix . 'user_notes',
                'type'           => 'textarea',
                'hide_from_rest' => false,
                'limit_type'     => 'character',
            ],
		],
	];
	return $meta_boxes;
});

// function my_sanitize_favorite_films_field( $value, $field, $old_value, $object_id ) {
//         if ( is_string( $value ) && !is_serialized( $value ) ) {
//         $value = maybe_serialize( $value );
//     }
//     return $value;
// }

/**
 * Hook into user profile page meta saving to ensure favorite films are saved as a single serialized array
 * instead of multiple rows.
 */
add_action( 'personal_options_update', 'wacp_fix_user_favorite_films_storage', 20 );
add_action( 'edit_user_profile_update', 'wacp_fix_user_favorite_films_storage', 20 );

function wacp_fix_user_favorite_films_storage( $user_id ) {

    // Make sure the current user can edit this profile
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return;
    }

    $meta_key = 'wacp-favorite_films';

    // Check if the field was submitted from the user edit form (Meta Box)
    if ( isset( $_POST[ $meta_key ] ) ) {
        $value = $_POST[ $meta_key ];

        // Normalize value: Meta Box can send a string if only one film is selected
        if ( ! is_array( $value ) ) {
            $value = array( $value );
        }

        // Clean and cast to integers
        $value = array_values( array_filter( array_map( 'intval', $value ) ) );

        // Delete any existing multiple rows
        delete_user_meta( $user_id, $meta_key );

        // Save as a single user meta row — WordPress will serialize automatically
        if ( ! empty( $value ) ) {
            add_user_meta( $user_id, $meta_key, $value );
        }
    }
}

/**
 * Make Meta Box display favorite films correctly in user profile.
 * Always return an array of post IDs, even if stored as a single serialized row.
 */
add_filter( 'rwmb_wacp-favorite_films_field_meta', function( $value, $field, $saved ) {

    // echo '##############rwmb_wacp-favorite_films_field_meta::::' . $field['id'];
    // print_r( $field );  var_dump( $value );

    // Get here current post ID in user profile admin editing page context 
    // Get the user being edited. On profile.php there is no user_id query var.
    if ( isset( $_GET['user_id'] ) ) {
        $user_id = intval( $_GET['user_id'] );
    } elseif ( isset( $_POST['user_id'] ) ) {
        $user_id = intval( $_POST['user_id'] );
    } else {
        // Fallback to current user (own profile page)
        $user_id = get_current_user_id();
    }

    if ( ! $user_id ) {
        // nothing to do
        return $value;
    }

    $post_id = $user_id;
    
    $raw_meta   = RWMB_Field::call( $field, 'raw_meta', $post_id );
    $meta = is_array( $raw_meta ) ? $raw_meta : [];

    // Array ( [0] => Array ( [0] => 139612 ) )
    // If it's an array of arrays, extract the first inner array
    if ( is_array( $meta ) && isset( $meta[0] ) && is_array( $meta[0] ) ) {
        $value = $meta[0];
    }

    return $value;
}, 20, 3 );