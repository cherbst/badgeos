<?php
/**
 * Custom Shortcodes
 *
 * @package BadgeOS
 * @subpackage Front-end
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Master Achievement List Short Code
 *
 * @since 1.0.0
 */
function badgeos_achievements_list_shortcode($atts){

	// check if shortcode has already been run
	if ( isset( $GLOBALS['badgeos_achievements_list'] ) )
		return;

	global $user_ID;
	extract( shortcode_atts( array(
		'type'        => 'all',
		'limit'       => '10',
		'show_filter' => 'true',
		'show_search' => 'true',
		'group_id'    => '0',
		'user_id'     => '0',
	), $atts ) );

	wp_enqueue_style( 'badgeos-front' );
	wp_enqueue_script( 'badgeos-achievements' );

	$data = array(
		'ajax_url'    => esc_url( admin_url( 'admin-ajax.php', 'relative' ) ),
		'type'        => $type,
		'limit'       => $limit,
		'show_filter' => $show_filter,
		'show_search' => $show_search,
		'group_id'    => $group_id,
		'user_id'     => $user_id,
	);
	wp_localize_script( 'badgeos-achievements', 'badgeos', $data );

	$post_type_plural = get_post_type_object( $type )->labels->name;

	$badges = null;

	$badges .= '<div id="badgeos-achievements-filters-wrap">';
		// Filter
		if ( $show_filter == 'false' ) {

			$filter_value = 'all';
			if( $user_id ){
				$filter_value = 'completed';
				$badges .= '<input type="hidden" name="user_id" id="user_id" value="'.$user_id.'">';
			}
			$badges .= '<input type="hidden" name="achievements_list_filter" id="achievements_list_filter" value="'.$filter_value.'">';

		}else{

			$badges .= '<div id="badgeos-achievements-filter">';

				$badges .= 'Filter: <select name="achievements_list_filter" id="achievements_list_filter">';

					$badges .= '<option value="all">All '.$post_type_plural;
					// If logged in
					if ( $user_ID >0 ) {
						$badges .= '<option value="completed">Completed '.$post_type_plural;
						$badges .= '<option value="not-completed">Not Completed '.$post_type_plural;
					}
					// TODO: if show_points is true "Badges by Points"
					// TODO: if dev adds a custom taxonomy to this post type then load all of the terms to filter by

				$badges .= '</select>';

			$badges .= '</div>';

		}

		// Search
		if ( $show_search != 'false' ) {

			$search = isset( $_POST['achievements_list_search'] ) ? $_POST['achievements_list_search'] : '';
			$badges .= '<div id="badgeos-achievements-search">';
				$badges .= '<form id="achievements_list_search_go_form" action="'. get_permalink( get_the_ID() ) .'" method="post">';
				$badges .= 'Search: <input type="text" id="achievements_list_search" name="achievements_list_search" value="'. $search .'">';
				$badges .= '<input type="submit" id="achievements_list_search_go" name="achievements_list_search_go" value="Go">';
				$badges .= '</form>';
			$badges .= '</div>';

		}

	$badges .= '</div><!-- #badgeos-achievements-filters-wrap -->';

	// Content Container
	$badges .= '<div id="badgeos-achievements-container"></div>';

	// Hidden fields and Load More button
	$badges .= '<input type="hidden" id="badgeos_achievements_offset" value="0">';
	$badges .= '<input type="hidden" id="badgeos_achievements_count" value="0">';
	$badges .= '<input type="button" id="achievements_list_load_more" value="Load More" style="display:none;">';
	$badges .= '<div class="badgeos-spinner"></div>';

	// Reset Post Data
	wp_reset_postdata();

	// Save a global to prohibit multiple shortcodes
	$GLOBALS['badgeos_achievements_list'] = true;
	return $badges;

}
add_shortcode( 'badgeos_achievements_list', 'badgeos_achievements_list_shortcode' );

function badgeos_nomination_form() {
	global $current_user, $post;

	//verify user is logged in to view any submission data
	if ( is_user_logged_in() ) {

		// check if step unlock option is set to submission review
		get_currentuserinfo();

		if ( badgeos_save_nomination_data() )
			printf( '<p>%s</p>', __( 'Nomination saved successfully.', 'badgeos' ) );

		// check if user already has a submission for this achievement type
		if ( ! badgeos_check_if_user_has_submission( $current_user->ID, $post->ID ) ) {

			// Step Description metadata
			// TODO: Check if this meta is still in use
			if ( $step_description = get_post_meta( $post->ID, '_badgeos_step_description', true ) )
				printf( '<p><span class="badgeos-submission-label">%s:</span></p>%s', __( 'Step Description', 'badgeos' ), wpautop( $step_description ) );

			return badgeos_get_nomination_form();

		}
		// user has an active submission, so show content and comments
		else {

			return badgeos_get_user_submissions();

		}

	}else{

		return '<p><i>' .__( 'You must be logged in to post a nomination.', 'badgeos' ) .'</i></p>';

	}

}
add_shortcode( 'badgeos_nomination', 'badgeos_nomination_form' );

function badgeos_submission_form() {
	global $current_user, $post;

	//verify user is logged in to view any submission data
	if ( is_user_logged_in() ) {

		// check if step unlock option is set to submission review
		get_currentuserinfo();

		if ( badgeos_save_submission_data() )
			printf( '<p>%s</p>', __( 'Submission saved successfully.', 'badgeos' ) );

		// check if user already has a submission for this achievement type
		if ( ! badgeos_check_if_user_has_submission( $current_user->ID, $post->ID ) ) {

			// Step Description metadata
			// TODO: Check if this meta is still in use
			if ( $step_description = get_post_meta( $post->ID, '_badgeos_step_description', true ) )
				printf( '<p><span class="badgeos-submission-label">%s:</span></p>%s', __( 'Step Description', 'badgeos' ), wpautop( $step_description ) );

			return badgeos_get_submission_form();

		}
		// user has an active submission, so show content and comments
		else {

			return badgeos_get_user_submissions();

		}

	}else{

		return '<p><i>' .__( 'You must be logged in to post a submission.', 'badgeos' ) .'</i></p>';

	}
}
add_shortcode( 'badgeos_submission', 'badgeos_submission_form' );
