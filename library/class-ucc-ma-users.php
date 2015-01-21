<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class UCC_Member_Approval extends WP_List_Table {
	function __construct() {
		parent::__construct( array(
			'singular' => 'user',
			'plural' => 'users',
			'ajax' => false
		) );
	}

	function prepare_items() {
		global $role, $usersearch;

		$usersearch = isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : '';

		$role = isset( $_REQUEST['role'] ) ? $_REQUEST['role'] : '';

		$per_page = 'users_per_page';
		$users_per_page = $this->get_items_per_page( $per_page );
		$users_per_page = 20;

		$paged = $this->get_pagenum();
		$paged = (int) ( isset( $_REQUEST['paged'] ) && ( absint( $_REQUEST['paged' ] ) > 1 ) ) ? absint( $_REQUEST['paged'] ) : 1;

		$args = array(
			'number' => $users_per_page,
			'offset' => ( $paged - 1 ) * $users_per_page,
			'role' => 'subscriber',
			'fields' => 'all_with_meta'
		);

		if ( '' !== $args['search'] )
			$args['search'] = '*' . $args['search'] . '*';

		if ( isset( $_REQUEST['orderby'] ) )
			$args['orderby'] = $_REQUEST['orderby'];
		else
			$args['orderby'] = 'registered';

		if ( isset( $_REQUEST['order'] ) )
			$args['order'] = $_REQUEST['order'];
		else
			$args['order'] = 'asc';

		// Query the user IDs for this page
		$wp_user_search = new WP_User_Query( $args );

		$this->items = $wp_user_search->get_results();

		$this->set_pagination_args( array(
			'total_items' => $wp_user_search->get_total(),
			'per_page' => $users_per_page,
		) );
	}

	function no_items() {
		_e( 'No matching users were found.' );
	}

	function get_views() {
		return array();
	}

	function extra_tablenav( $which ) {
		return;
	}

	function get_columns() {
		$c = array(
			'cb'       => '<input type="checkbox" />',
			'username' => __( 'Username' ),
			'email'    => __( 'E-mail Address' ),
			'profile'  => __( 'Profile' ),
			'activation'  => __( 'Pending Activation' ),
			'registerdate' => __( 'Registration Date' ),
			'notes' => __( 'Notes' ),
			'approve_member' => __( 'Approve Member' )
		);

		return $c;
	}

	function get_sortable_columns() {
		$c = array(
			'username' => array( 'login', false ),
			'email'    => array( 'email', false ),
			'registerdate' => array( 'registered', false )
		);

		return $c;
	}

	function get_column_info() {
		$columns = $this->get_columns();
		$sortable = $this->get_sortable_columns();
		$hidden = array();

		$column_info = array( $columns, $hidden, $sortable ); 
		return $column_info;
	}

    function get_bulk_actions() {
        $actions = array();
        
        if ( current_user_can( 'delete_users' ) || current_user_can( 'remove_users' ) ) {
        	$actions['approve'] = __( 'Approve Members' );
        	$actions['ban'] = __( 'Ban Users' );
	        if ( is_multisite() ) {
	            $actions['remove'] = __( 'Remove Users' );
       		} else {
            	if ( current_user_can( 'delete_users' ) )
                	$actions['delete'] = __( 'Delete Users' );
        	}

        	return $actions;
        }
    }

	function display_rows() {
		$post_counts = count_many_users_posts( array_keys( $this->items ) );

		$style = '';
		foreach ( $this->items as $userid => $user_object ) {
			$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
			echo "\n\t", $this->single_row( $user_object, $style, isset( $post_counts ) ? $post_counts[ $userid ] : 0 );
		}
	}

	/**
	 * Generate HTML for a single row on the users.php admin panel.
	 *
	 * @since 2.1.0
	 *
	 * @param object $user_object
	 * @param string $style Optional. Attributes added to the TR element.  Must be sanitized.
	 * @param string $role Key for the $wp_roles array.
	 * @param int $numposts Optional. Post count to display for this user.  Defaults to zero, as in, a new user has made zero posts.
	 * @return string
	 */
	function single_row( $user_object, $style = '', $numposts = 0 ) {
		if ( !( is_object( $user_object ) && is_a( $user_object, 'WP_User' ) ) )
			$user_object = new WP_User( (int) $user_object );
		$user_object = sanitize_user_object( $user_object, 'display' );
		$email = $user_object->user_email;

			$url = 'users.php?';

		$checkbox = '';
		// Check if the user for this row is editable
		if ( current_user_can( 'list_users' ) ) {
			// Set up the user editing link
			// TODO: make profile/user-edit determination a separate function
			if ( get_current_user_id() == $user_object->ID ) {
				$edit_link = 'profile.php';
			} else {
				$edit_link = esc_url( add_query_arg( 'wp_http_referer', urlencode( stripslashes( $_SERVER['REQUEST_URI'] ) ), "user-edit.php?user_id=$user_object->ID" ) );
			}

			// Set up the checkbox ( because the user is editable, otherwise its empty )
			$checkbox = "<input type='checkbox' name='users[]' id='user_{$user_object->ID}' value='{$user_object->ID}' />";
			$edit = '<strong>' . $user_object->user_login . '</strong>';
		} else {
			$edit = '<strong>' . $user_object->user_login . '</strong>';
		}
		$avatar = get_avatar( $user_object->ID, 32 );

		$r = "<tr id='user-$user_object->ID'$style>";

		list( $columns, $hidden, $sortable ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$class = "class=\"$column_name column-$column_name\"";

			$style = '';

			$attributes = "$class$style";

			switch ( $column_name ) {
				case 'cb':
					$r .= '<th scope="row" class="check-column">';
					$r .= '<input type="checkbox" name="ucc_ma_users[' . esc_attr__( $user_object->ID ) . ']" value="checked" />';
					$r .= '</th>' . "\n";
					break;
				case 'username':
					$r .= "<td $attributes>$avatar $edit</td>";
					break;
				case 'email':
					$r .= "<td $attributes><a href='mailto:$email' title='" . esc_attr( sprintf( __( 'E-mail: %s' ), $email ) ) . "'>$email</a></td>";
					break;
				case 'profile':
					$r .= "<td $attributes>";
					if ( function_exists( 'bp_core_get_user_domain' ) )
						$r .= '<a target="_blank" href="' . bp_core_get_user_domain( $user_object->ID ) . '">' . __( 'View Profile' ) . '</a>';				
					$r .= "</td>";
					break;
				case 'activation':
					$key = get_user_meta( $user_object->ID, 'activation_key' );
					$status = (int) $user_object->user_status;
					if ( ! empty( $key ) && ( $status == 2 ) )
						$value = 'Email not validated.';
					else
						$value = '';
					$r .= "<td $attributes>";
					$r .= $value;
					$r .= "</td>";
					break;
				case 'registerdate':
					$registerdate = ( ( $time = strtotime ( get_date_from_gmt ( $user_object->user_registered ) ) ) ) ? esc_html( date ("D M jS, Y", $time ) ) . '<br /><small>@ precisely ' . esc_html( date ( "g:i a", $time ) ) . '</small>' : "—";
					$r .= "<td $attributes>";
					$r .= $registerdate;
					$r .= "</td>";
					break;
				case 'notes':
					global $wpdb;
					$admin_notes = get_user_meta( $user_object->ID, $wpdb->prefix . 's2member_notes', true );
					if ( ! empty( $admin_notes ) )
						$admin_notes .= '<br />';
					if(class_exists('BP_Member_Notes')){
						$member_notes = new BP_Member_Notes;
						$bpmn = $member_notes->get_note($user_object->ID);
						$notes = '';
						if(!empty($bpmn)){
						  if(!empty ($admin_notes)) $notes .= "<hr />"; 
						  $notes .= $bpmn;
						  $notes .= "<br>";
						}
					}
					$r .= "<td $attributes>";
					if ( current_user_can( 'edit_user',  $user_object->ID ) ) {
						$edit_url = esc_url( add_query_arg( 'wp_http_referer', urlencode( stripslashes( $_SERVER['REQUEST_URI'] ) ), "user-edit.php?user_id=$user_object->ID" ) );
						$edit_link = '<a href="' . $edit_url . '">Add Note / Edit User</a>';
					}
					$r .= $admin_notes . $notes . $edit_link;
					$r .= "</td>";
					break;
				case 'approve_member':
					$nonce = wp_create_nonce( 'ucc_ma_nonce' );
					$r .= "<td $attributes>";
                                        $r .= '<a href="' . admin_url( "index.php?page=ucc_member_approval&amp;action=approve&amp;user={$user_object->ID}&amp;_wpnonce={$nonce}" ) . '" class="button">Approve Member</a>'; 
                                        $r .= "</td>";
					break;
				default:
					$r .= "<td $attributes>";
					$r .= apply_filters( 'manage_users_custom_column', '', $column_name, $user_object->ID );
					$r .= "</td>";
			}
		}
		$r .= '</tr>';

		return $r;
	}

	function display() {
		extract( $this->_args );

		$this->display_tablenav( 'top' );

?>
<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
	<thead>
	<tr>
		<?php $this->print_column_headers(); ?>
	</tr>
	</thead>

	<tfoot>
	<tr>
		<?php $this->print_column_headers( false ); ?>
	</tr>
	</tfoot>

	<tbody id="the-list"<?php if ( $singular ) echo " class='list:$singular'"; ?>>
		<?php $this->display_rows_or_placeholder(); ?>
	</tbody>
</table>
<?php
		$this->display_tablenav( 'bottom' );
	}
}

