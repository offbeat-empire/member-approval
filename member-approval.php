<?php
/*
Plugin Name: Member Approval
Plugin URI: http://uncommoncontent.com/wordpress/plugins/member-approval
Description: Create a simpler User admin screen for member role changes. 
Version: 0.2
Author: Jennifer M. Dodd
Author URI: http://bajada.net
*/ 

/*
	Copyright 2012 Jennifer M. Dodd (email: jmdodd@gmail.com)

	This program is free software; you can redistribute it and/or 
	modify it under the terms of the GNU General Public License 
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

define( 'UCC_MA_PLUGINLIB', plugin_dir_path( __FILE__ ) . 'library/' );
include ( UCC_MA_PLUGINLIB . 'class-ucc-ma-users.php' );

if ( ! function_exists( 'ucc_member_approval_add_query_var' ) ) {
function ucc_member_approval_add_query_var() {
        global $wp;
        $wp->add_query_var( 'registration' );
} }
add_action( 'init', 'ucc_member_approval_add_query_var' );

if ( ! function_exists( 'ucc_member_approval_menu' ) ) {
function ucc_member_approval_menu() {
        add_dashboard_page( 'Member Approval', 'Member Approval', 'edit_others_posts', 'ucc_member_approval', 'ucc_member_approval' );
} }
add_action( 'admin_menu', 'ucc_member_approval_menu' );

if ( ! function_exists( 'ucc_member_approval' ) ) {
function ucc_member_approval() {
	include( plugin_dir_path( __FILE__ ) . 'users.php' );
} }

