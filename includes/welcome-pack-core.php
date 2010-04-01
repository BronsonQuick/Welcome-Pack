<?php
define( 'WELCOME_PACK_IS_INSTALLED', 1 );

if ( !defined( 'WELCOME_PACK_AUTOACCEPT_INVITATIONS' ) )
	define( 'WELCOME_PACK_AUTOACCEPT_INVITATIONS', false );


/* The notifications file should contain functions to send email notifications on specific user actions */
require( dirname( __FILE__ ) . '/welcome-pack-notifications.php' );

/* The filters file should create and apply filters to component output functions. */
require( dirname( __FILE__ ) . '/welcome-pack-filters.php' );

if ( file_exists( dirname( __FILE__ ) . '/languages/' . get_locale() . '.mo' ) )
	load_textdomain( 'dpw', dirname( __FILE__ ) . '/languages/' . get_locale() . '.mo' );

function dpw_add_admin_menu() {
	global $bp;

	if ( !$bp->loggedin_user->is_site_admin )
		return false;

	require ( dirname( __FILE__ ) . '/welcome-pack-admin.php' );

	add_options_page( __( 'Welcome Pack settings', 'dpw' ), __( 'Welcome Pack', 'dpw' ), 'administrator', 'welcome-pack', 'dpw_admin_screen' );
	add_action( 'admin_init', 'dpw_admin_register_settings' );
}
add_action( 'admin_menu', 'dpw_add_admin_menu' );

function dpw_on_user_registration( $user_id ) {
	if ( !$settings = get_site_option( 'welcomepack' ) )
		return;

	$settings = maybe_unserialize( $settings );
	if ( !$settings || !is_array( $settings ) )
		return;

	if ( $settings['friendstoggle'] && function_exists( 'friends_install' ) )
		foreach ( $settings['friends'] as $friend_id )
			friends_add_friend( $friend_id, $user_id, WELCOME_PACK_AUTOACCEPT_INVITATIONS );

	if ( $settings['groupstoggle'] && function_exists( 'groups_install' ) ) {
		foreach ( $settings['groups'] as $group_id ) {
			$group = new BP_Groups_Group( $group_id );
			groups_invite_user( array( 'user_id' => $user_id, 'group_id' => $group_id, 'inviter_id' => $group->creator_id, 'is_confirmed' => WELCOME_PACK_AUTOACCEPT_INVITATIONS ) );
			groups_send_invites( $group->creator_id, $group_id );
		}
	}

	if ( $settings['welcomemsgtoggle'] && function_exists( 'messages_install' ) ) {
		if ( !$settings['welcomemsgsender'] || !$settings['welcomemsgsubject'] || !$settings['welcomemsg'] )
			return;

		messages_new_message( array( 'sender_id' => $settings['welcomemsgsender'], 'recipients' => array( $settings['welcomemsgsender'], $user_id ), 'subject' => $settings['welcomemsgsubject'], 'content' => $settings['welcomemsg'] ) );
	}
}
add_action( 'user_register', 'dpw_on_user_registration', 11 );
?>