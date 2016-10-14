<?php

/*
Plugin Name: Post-Likes
Plugin URI:
Description: Add the ablility to "like" posts
Version: 0.0.1
Author: Tyler Cherpak
Author URI: http://github.com/tylercherpak
*/

class Post_Likes {

	const COMMENT_TYPE = 'like-comment';

	public static function init(){
		add_action( 'wp_ajax_post_like', array( __CLASS__, 'like' ) );
		add_action( 'wp_ajax_post_unlike', array( __CLASS__, 'unlike' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_js' ) );
		add_filter( 'the_content', array( __CLASS__, 'filter_the_content' ) );
	}

	public static function enqueue_js(){
		$plugin_dir = plugin_dir_url( __FILE__ );
		wp_enqueue_script( 'post-like', $plugin_dir . 'post-likes.js', array(), false, true );
		wp_localize_script( 'post-like', 'ajax_object', array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => wp_create_nonce( 'like_nonce' )
		) );
	}

	public static function get_post_like_template( $post_id = false ){
		if( empty( $post_id ) ){
			$post_id = get_the_ID();
		}
		if ( ! is_user_logged_in() ) {
			$class = 'login-to-like';
			$text = 'login to like';
		} else {
			$users_like_comments = self::get_user_like_comment( $post_id );
			$class = 'like';
			$text  = 'click to like';
			$toggle = 'like';
			if ( ! empty( $users_like_comments ) ) {
				$users_like_comment = array_shift( $users_like_comments );
				$users_like_karma   = $users_like_comment->comment_karma;
				$toggle = ( $users_like_karma == 0 ) ? 'like' : 'unlike';
				$text  = ( $users_like_karma == 0 ) ? 'click to like' : 'click to unlike';
			}
		}
		$like_html = sprintf( '<a class="%1$s" data-post-id="%2$s" data-like-toggle="%4$s">%3$s</a>', esc_attr( $class ), esc_attr( $post_id ), esc_html( $text ), esc_attr( $toggle ) );
		return $like_html;
	}

	public static function filter_the_content( $content ){
		$like_html = self::get_post_like_template();
		$content .= $like_html;
		return $content;
	}

	public static function like(){
		if ( empty( $_POST ) || empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'like_nonce' ) ){
			wp_send_json_error( 'nonce error' );
			die;
		}
		if ( empty( $_POST['post_id'] ) || empty( $user_id = get_current_user_id() ) ) {
			wp_send_json_error( 'post id or user not found' );
			die;
		}
		$post_id = ( int ) $_POST['post_id'];
		$users_like_comment = self::get_user_like_comment( $post_id );
		if( empty( $users_like_comment ) ){
			$comment_id = self::insert_like_comment( $post_id );
		}else{
			$comment = array_shift( $users_like_comment );
			$comment_id = self::update_like_comment( $comment->comment_ID );
		}
		wp_send_json_success( $comment_id );
		die();
	}

	public static function unlike(){
		$comment_id = '';
		if ( empty( $_POST ) || empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'like_nonce' ) ){
			wp_send_json_error( 'nonce error' );
			die;
		}
		if ( empty( $_POST['post_id'] ) || empty( $user_id = get_current_user_id() ) ) {
			wp_send_json_error( 'post id or user not found' );
			return false;
		}
		$post_id = ( int ) $_POST['post_id'];
		$users_like_comment = self::get_user_like_comment( $post_id );
		if ( ! empty( $users_like_comment ) ) {
			$comment = array_shift( $users_like_comment );
			$comment_id = self::update_like_comment( $comment->comment_ID, true );
		}
		wp_send_json_success( $comment_id );
		die();
	}

	public static function insert_like_comment( $post_id = false ){
		if ( empty( $post_id ) || empty( $user_id = get_current_user_id() ) ) {
			return false;
		}
		return wp_new_comment([
			'comment_post_ID' => $post_id,
			'user_id'         => $user_id,
			'comment_type'    => self::COMMENT_TYPE,
			'comment_karma'   => 1
		]);
	}

	public static function update_like_comment( $comment_id = false, $unlike = false ){
		if ( empty( $comment_id ) || empty( $user_id = get_current_user_id() ) ) {
			return false;
		}
		$karma = $unlike ? 0 : 1;
		return wp_update_comment([
			'comment_ID'      => $comment_id,
			'user_id'         => $user_id,
			'comment_type'    => self::COMMENT_TYPE,
			'comment_karma'   => $karma
		]);
	}

	public static function get_user_like_comment( $post_id = false ){
		if ( empty( $post_id ) || empty( $user_id = get_current_user_id() ) ) {
			return false;
		}
		$users_like_comment = get_comments([
			'post_id' => $post_id,
			'user_id' => $user_id,
			'type'    => self::COMMENT_TYPE,
			'number'  => 1
		]);
		return $users_like_comment;
	}
}
add_action( 'init', array( 'Post_Likes', 'init' ) );