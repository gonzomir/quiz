<?php
/*
Plugin Name: Quiz
Plugin URI: http://wordpress.org/extend/plugins/quiz/
Version: 1.3.1
Description: You provide a question for each post or page. Visitors must answer the question correctly to comment, unless they have post publishing capabilities.
Author: <a href="http://andyskelton.com/">Andy Skelton</a>, <a href="http://striderweb.com/nerdaphernalia/">Stephen Rider</a>, and <a href="http://coveredwebservices.com/">Mark Jaquith</a>
Text Domain: quiz
Domain Path: /lang
*/

// To manually place the quiz form in your comments form, use do_action('show_comment_quiz')

/*
	FIXME: Some translation strings contain code or other content that should not be translatable
	TODO: "Wrong answer" page -- Show comment content and a caution to copy content before going back
*/

class Comment_Quiz_Plugin {
	public static $instance;
	public $option_version = '1.2';
	public $option_name = 'plugin_commentquiz_settings';

	public function __construct() {
		self::$instance = $this;
		load_plugin_textdomain( 'quiz', false, basename( dirname( __FILE__ ) ) . '/lang' );
		$options = get_option( $this->option_name );

		if ( ! isset( $options['last_opts_ver'] ) || $options['last_opts_ver'] != $this->option_version ) {
			$this->set_defaults();
		}
		if ( ! is_admin() ) {
			// This is so end users can use do_action('show_comment_quiz') in themes
			add_action( 'show_comment_quiz', [ $this, 'the_quiz' ] );
			// ...otherwise will add form automatically
			add_action( 'comment_form_after_fields', [ $this, 'the_quiz' ] );
			add_action( 'comment_form', [ $this, 'the_quiz' ] ); // compatibility with older themes that lack 'comment_form_after_fields' hook

			add_filter( 'preprocess_comment', [ $this, 'process' ], 1 );
		}
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_menu', [ $this, 'call_meta_box' ] );
		add_action( 'save_post', [ $this, 'save_meta_box' ] );
		add_action( 'wp_ajax_validate_quiz', [ $this, 'ajax_callback' ] );
		add_action( 'wp_ajax_nopriv_validate_quiz', [ $this, 'ajax_callback' ] );
	}

	public function get_quiz( $id = null, $blankdefault = false ) {
		if ( ! $id ) {
			$id = $GLOBALS['post']->ID;
		}
		$quiz = (array) get_post_meta( $id, 'quiz', true );
		if ( ( isset( $quiz['q'] ) && 'noquiz' === $quiz['q'] ) || ( isset( $quiz['a'] ) && 'noquiz' === $quiz['a'] ) ) {
			return false;
		}
		if ( empty( $quiz ) || empty( $quiz['q'] ) || empty( $quiz['a'] ) ) {
			if ( $blankdefault ) {
				$quiz = [
					'q' => '',
					'a' => '',
				];
			} else {
				$options = get_option( $this->option_name );
				$quiz = [
					'q' => $options['def_q'] ?: '',
					'a' => $options['def_a'] ?: '',
				];
			}
		}

		return $quiz;
	}

	public function set_quiz( $post_id, $quiz ) {
		$allowedtags = [
			'abbr' => [
				'title' => [],
			],
			'acronym' => [
				'title' => [],
			],
			'b' => [],
			'strong' => [],
			'br' => [],
			'code' => [],
			'em' => [],
			'i' => [],
			'q' => [
				'cite' => [],
			],
			'strike' => [],
			'sub' => [],
			'sup' => [],
			'u' => [],
		];
		foreach ( $quiz as $key => $value ) {
			$quiz[ $key ] = wp_kses( $value, $allowedtags );
		}

		update_post_meta( $post_id, 'quiz', $quiz );
	}

	public function ajax_callback() {
		$quiz = $this->get_quiz( intval( wp_unslash( $_REQUEST['post_id'] ?? 0 ) ) );
		if ( $quiz ) {
			$answers = array_map( 'trim', explode( ',', $quiz['a'] ) );
			foreach ( $answers as $answer ) {
				if ( $this->compare( $answer, sanitize_text_field( wp_unslash( $_REQUEST['a'] ?? '' ) ) ) ) {
					wp_die( 0 );
				}
			}
			wp_die( 1 );
		} else {
			wp_die( 0 );
		}
	}

	public function get_quiz_form( $html = false, $validate = true ) {
		$def_quiz_form = sprintf(
			'<p id="commentquiz" style="clear:both">
				<label for="quiz">%s</label><input type="text" name="quiz" id="quiz" size="22" value="" />
			</p>',
			// TRanslators: %quistion% is replaced with actual anti-spam question.
			esc_html__( 'Anti-Spam Quiz: %question%', 'quiz' )
		);
		$options = get_option( $this->option_name );
		if ( ! $options ||
			empty( $options['quiz_form'] ) ||
			( $validate && ! strpos( $options['quiz_form'], '%question%' ) )
		) {
			$quiz_form = $def_quiz_form;
		} else {
			$quiz_form = $options['quiz_form'];
		}

		if ( $html ) {
			$quiz_form = htmlspecialchars( $quiz_form );
		}
		return $quiz_form;
	}

	public function upgrade_slashing_12( $curr_options ) {
		update_option( $this->option_name, stripslashes_deep( (array) $curr_options ) );
		return get_option( $this->option_name );
	}

	public function set_defaults( $mode = 'merge' ) {
		// $mode can be set to "unset" or "reset"
		if ( 'unset' == $mode ) {
			delete_option( $this->option_name );
			return true;
		}

		$defaults = [
			'last_opts_ver' => $this->option_version,
			'def_q' => __( 'Which is warmer, ice or steam?', 'quiz' ),
			'def_a' => __( 'steam', 'quiz' ),
			'quiz_form' => $this->get_quiz_form(),
		];

		if ( 'reset' == $mode ) {
			delete_option( $this->option_name );
			add_option( $this->option_name, $defaults );
		} else if ( $curr_options = get_option( $this->option_name ) ) {
			// Merge existing prefs with new or missing defaults

			// Version-specific upgrades
			if ( ! isset( $curr_options['last_opts_ver'] ) || version_compare( $curr_options['last_opts_ver'], '1.2', '<' ) ) {
				$curr_options = $this->upgrade_slashing_12( $curr_options ); // Upgrade to remove slashes
			}

			// Merge
			$curr_options = array_merge( $defaults, $curr_options );
			$curr_options['last_opts_ver'] = $this->option_version; // always update
			update_option( $this->option_name, $curr_options );
		} else {
			add_option( $this->option_name, $defaults );
		}
		return true;
	}
	// ****************************
	// Comment Form Functions
	// ****************************

	public $form_shown = 0;

	public function the_quiz() {
		// only show the form once on a page
		if ( $this->form_shown++ > 0 ) {
			return false;
		}

		global $current_user, $post, $id;
		$quiz = $this->get_quiz( $id );
		if ( ! $quiz ) {
			return false;
		}
		$quiz_form = $this->get_quiz_form();

		echo str_replace( '%question%', $quiz['q'], $quiz_form );
		add_action( 'wp_print_footer_scripts', [ $this, 'form_position' ] );
		return true;
	}

	// try to put form in a better location than _after_ the submit button!
	public function form_position() {
		// if the the_quiz() was only called from comment_form hook...
		if ( did_action( 'show_comment_quiz' ) > 0 || did_action( 'comment_form_after_fields' ) > 0 ) {
			return false;
		}

		$form_position = '
<script>
var u=document.getElementById("comment");
if ( u ) {
	u.parentNode.parentNode.insertBefore(document.getElementById("commentquiz"), u.parentNode);
}
</script>
';
		echo $form_position;
		return true;
	}

	public function process( $commentdata ) {
		extract( $commentdata );
		if ( ! current_user_can( 'publish_posts' ) &&
			$comment_type != 'pingback' &&
			$comment_type != 'trackback' ) {
			$quiz = $this->get_quiz( $comment_post_ID );

			if ( $quiz ) {
				if ( empty( $_POST['quiz'] ) ) {
					wp_die( __( 'You must answer the question to post a comment. Please <a href="javascript:window.history.back()">go back</a> and try again.', 'quiz' ) );
				}

				$answer = $quiz['a'];
				$answer = array_map( 'trim', explode( ',', $answer ) );
				$response = wp_unslash( $_POST['quiz'] );

				foreach ( $answer as $a ) {
					if ( $this->compare( $a, $response ) ) {
						return $commentdata;
					}
				}
				wp_die( __( 'You answered the question incorrectly.  Please <a href="javascript:window.history.back()">go back</a> and try again.', 'quiz' ) );
			}
		}
		return $commentdata;
	}

	public function compare( $a, $b ) {
		$a = trim( strtolower( strip_tags( $a ) ) );
		$b = trim( strtolower( strip_tags( $b ) ) );

		return apply_filters( 'comment_quiz_compare', $a === $b, $a, $b );
	}

	// ************************
	// Meta Box Functions
	// for post edit page
	// ************************

	public function call_meta_box() {
		if ( function_exists( 'add_meta_box' ) ) {
			add_meta_box( 'edit_comment_quiz', 'Comment Quiz', [ $this, 'meta_box' ], 'post', 'normal' );
			add_meta_box( 'edit_comment_quiz', 'Comment Quiz', [ $this, 'meta_box' ], 'page', 'normal' );
		}
	}

	public function meta_box() {
		global $post;
		$quiz = $this->get_quiz( $post->ID, true );
		if ( $quiz ) {
			$q = esc_attr( $quiz['q'] );
		} else {
			$q = 'noquiz';
		}
		$a = esc_attr( $quiz['a'] );
		$nonce = wp_create_nonce( plugin_basename( __FILE__ ) );
		$howto1 = __( 'Enter "noquiz" if you don\'t want a question for this post, or leave it blank to use the default question.', 'quiz' );
		$howto2 = __( 'You may enter multiple correct answers, separated by commas; e.g. <code>color, colour</code>', 'quiz' );
		$qlabel = __( 'Question', 'quiz' );
		$alabel = __( 'Answer', 'quiz' );
		echo sprintf(
			'<input type="hidden" name="comment_quiz_metabox" id="comment_quiz_metabox" value="%1$s" />
			<p>
			<label for="quizQuestion">%3$s</label><br />
			<input type="text" name="quizQuestion" id="quizQuestion" size="25" value="%2$s" />
			</p>
			<p>
			<label for="quizAnswer">%5$s</label><br />
			<input type="text" name="quizAnswer" id="quizAnswer" size="25" value="%4$s" />
			</p>
			<p class="howto">%6$s<br />%7$s</p>',
			esc_attr( $nonce ),
			esc_attr( $q ),
			esc_html( $qlabel ),
			esc_attr( $a ),
			esc_html( $alabel ),
			esc_html( $howto1 ),
			wp_kses_post( $howto2 )
		);
	}

	public function save_meta_box( $post_id ) {

		if ( ! isset( $_POST['comment_quiz_metabox'] ) || ! wp_verify_nonce( wp_unslash( $_POST['comment_quiz_metabox'] ?? '' ), plugin_basename( __FILE__ ) ) ) {
			return $post_id;
		} else if ( 'page' == wp_unslash( $_POST['post_type'] ?? 'post' ) ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} else if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		$new_quiz = [
			'q' => sanitize_text_field( wp_unslash( $_POST['quizQuestion'] ?? '' ) ),
			'a' => sanitize_text_field( wp_unslash( $_POST['quizAnswer'] ?? '' ) ),
		];
		$this->set_quiz( $post_id, $new_quiz );

		return $post_id;
	}

	// *****************************
	// Settings Page Functions
	// *****************************

	public function options_url() {
		return admin_url( 'options-general.php?page=quiz' );
	}

	public function add_settings_page() {
		if ( current_user_can( 'manage_options' ) ) {
			$page = add_options_page( __( 'Comment Quiz', 'quiz' ), __( 'Quiz', 'quiz' ), 'manage_options', 'quiz', [ $this, 'settings_page' ] );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'filter_plugin_actions' ] );
			add_action( 'load-' . $page, [ $this, 'save_options' ] );
			return $page;
		}
		return false;
	}

	// Add action link(s) to plugins page
	public function filter_plugin_actions( $links ) {
		$settings_link = '<a href="' . $this->options_url() . '">' . __( 'Settings' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	// this public function used by the settings page to display set options in the form controls when the page is opened
	public function checktext( $options, $optname, $optdefault = '' ) {
		// for text boxes and textareas
		return ( $options[ $optname ] ) ? $options[ $optname ] : $optdefault;
	}

	// Saving the options
	public function save_options() {
		if ( isset( $_POST['save_settings'] ) ) {
			check_admin_referer( 'commentquiz-update-options' );
			$posted_data = wp_unslash( (array) $_POST['commentquiz_options'] ?? [] );
			$options = [
				'def_q' => sanitize_text_field( $posted_data['def_q'] ),
				'def_a' => sanitize_text_field( $posted_data['def_a'] ),
				'quiz_form' => $posted_data['quiz_form'],
			];
			update_option( $this->option_name, $options );
			wp_redirect( add_query_arg( 'updated', 1 ) );
			exit();
		}
	}

	// finally, the Settings Page itself
	public function settings_page() {
		// get options for use in formsetting functions
		$opts = get_option( $this->option_name );

		?>
<div class="wrap">
	<h2><?php esc_html_e( 'Comment Quiz', 'quiz' ); ?></h2>
	<form method="post" action="<?php echo esc_url( $this->options_url() ); ?>">
		<?php
		if ( function_exists( 'wp_nonce_field' ) ) {
			wp_nonce_field( 'commentquiz-update-options' );
		}
		?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Default Quiz', 'quiz' ); ?></th>
					<td>
						<p>
							<label for="def_q"> <?php esc_html_e( 'Question', 'quiz' ); ?></label><br />
							<input type="text" name="commentquiz_options[def_q]" id="def_q" size="35" value="<?php echo esc_attr( $this->checktext( $opts, 'def_q', '' ) ); ?>" />
						</p>
						<p>
							<label for="def_a"> <?php esc_html_e( 'Answer', 'quiz' ); ?></label><br />
							<input type="text" name="commentquiz_options[def_a]" id="def_a" size="15" value="<?php echo esc_attr( $this->checktext( $opts, 'def_a', '' ) ); ?>"/>
						</p>
						<p><?php echo wp_kses_post( __( 'You may enter multiple correct answers, separated by commas; e.g. <code>color, colour</code>', 'quiz' ) ); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Quiz Form', 'quiz' ); ?></th>
					<td><textarea name="commentquiz_options[quiz_form]" id="quiz_form" cols="60" rows="6"><?php echo esc_textarea( $this->get_quiz_form( false, false ) ); ?></textarea><br />
					<span><?php echo wp_kses_post( __( 'The form must contain a %question% placeholder.<br />To reset to default, blank this field and save settings.', 'quiz' ) ); ?></span>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="submit">
			<input type="submit" name="save_settings" class="button-primary" value="<?php esc_html_e( 'Save Changes' ); ?>" /></div>
	</form>
</div><!-- wrap -->
		<?php
	}

} // END class commentquiz

new Comment_Quiz_Plugin();

// register_activation_hook( __FILE__, array( $commentquiz, 'set_defaults' ) );

// DEPRECATED -- backwards compatibility only -- use do_action('show_comment_quiz')
function the_quiz() {
	_deprecated_function( __FUNCTION__, '0.0', 'do_action(\'show_comment_quiz\')' );
	do_action( 'show_comment_quiz' );
}
