<?php

if ( ! current_user_can( 'edit_others_posts' ) )
	wp_die( __( 'You do not have permission to access this page.' ) );

// Deal with _GET approve_member.
if ( isset( $_GET['_wpnonce'] ) && isset( $_GET['action'] ) && ( 'approve' == $_GET['action'] ) ) {
	$msg = '';
	$nonce = $_GET['_wpnonce'];

	if( ! wp_verify_nonce( $nonce, 'ucc_ma_nonce' ) ) 
		die( "Strange nonce." );

        // Process actual data here.
        if ( ! current_user_can( 'promote_users' ) )
        	wp_die( __( 'You can&#8217;t edit users.' ) );

        $editable_roles = get_editable_roles();
        if ( empty( $editable_roles['s2member_level1'] ) )
        	var_dump($editable_roles);
        	wp_die( __( 'You can&#8217;t give users that role.' ) );

        $user = $_GET['user'];
        $id = absint( $user );

        if ( ! current_user_can( 'promote_user', $id ) )
        	wp_die( __( 'You can&#8217;t edit that user.' ) );

        $user = new WP_User( $id );
		$user->set_role('s2member_level1');
	$msg .= 'Member approved.';
}

// Deal with _POST approve_member, spam_user
if ( ! empty( $_POST ) && check_admin_referer( basename( __FILE__ ), 'ucc_ma_user_nonce' ) ) {
	$msg = '';
	
	if ( isset( $_POST['ucc_ma_users'] ) )
		$_users = $_POST['ucc_ma_users'];

	if ( empty( $_users ) )
		$_users = array();
		
	$user_ids = array();
	foreach ( $_users as $user => $checked ) {
		$ids[] = absint( $user );
	}

	if ( empty( $ids ) ) {
		$msg = 'No users specified.';
	} else {
		$action = ( isset( $_POST['action'] ) && $_POST['action'] == -1 ? $_POST['action2'] : $_POST['action'] );
		switch ( $action ) {
			case 'approve':
				if ( ! current_user_can( 'promote_users' ) )
			    	wp_die( __( 'You can&#8217;t edit users.' ) );
			
				$editable_roles = get_editable_roles();
				if ( empty( $editable_roles['s2member_level1'] ) )
				    wp_die( __( 'You can&#8217;t give users that role.' ) );
				    
				foreach ( $ids as $id ) {
				        if ( ! current_user_can( 'promote_user', $id ) )
						wp_die( __( 'You can&#8217;t edit that user.' ) );
			
			        	$user = new WP_User( $id );
			        	$user->set_role( 's2member_level1' );
				}
				$msg .= 'Members approved.';
				break;
				
			case 'delete':
				if ( ! current_user_can( 'delete_users' ) )
					wp_die( __( 'You can&#8217;t delete users.' ) );
			
				foreach ( $ids as $id ) {
					if ( ! current_user_can( 'delete_user', $id ) )
						wp_die( __( 'You can&#8217;t delete that user.' ) );
					  
					wp_delete_user( $id );			
				}
				$msg .= 'Users deleted.';
				break;
			
			case 'ban':
				if ( ! current_user_can( 'promote_users' ) )
			    		wp_die( __( 'You can&#8217;t edit that user.' ) );
					    
				foreach ( $ids as $id ) {
			       	if ( ! current_user_can( 'promote_user', $id ) )
			       		wp_die( __( 'You can&#8217;t edit that user.' ) );
				
					//buddypress compatibility
			        if(function_exists('bp_core_process_spammer_status')) {
			        	bp_core_process_spammer_status( $id, 'spam', true);
			        } else if (is_multisite()) {
			        	update_user_status( $id, 'spam', true);
			        }

			        //Assign 'spammer' role if it exists
			        $editable_roles = get_editable_roles();
			        if ( ! empty( $editable_roles['spammer'] ) ) {
			        	$user = new WP_User( $id );
			        	$user->set_role( 'spammer' );
			        }
				}
				$msg .= 'Users banned.';
				break;
		}
	}
}

if ( ! empty( $msg ) )
	$msg = '<div class="updated" id="message"><p>' . $msg . '</p></div>';

$wp_list_table = new UCC_Member_Approval();
$wp_list_table->prepare_items();

?>
<div class="wrap">
<?php screen_icon( 'users' ); ?>
<h2><?php esc_html_e( 'Member Approval' ) ?>
<?php echo $msg; ?></h2>

<form id="ucc-ma-user-list" action="" method="post">
<?php wp_nonce_field( basename( __FILE__ ), 'ucc_ma_user_nonce' ); ?>
<?php $wp_list_table->display(); ?>
</form>
</div>
<?php
