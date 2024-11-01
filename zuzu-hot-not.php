<?php
/*
Plugin Name: Zuzu Hot or Not
Plugin URI: http://tiguandesign.com/
Description: A simple and lightweight plugin that lets visitors to decide whether a story is hot or not.
Author: Tiguan
Author URI:  http://tiguandesign.com/
Licence: GPLv2
Version: 1.0
Stable Tag: 1.0
*/

class Zuzu_Hot_Not {


	function __construct() {
		$this->defaults = array(
			'hot' => "Hot",
			'not' => "Not",
			'boxtitle' => "Hot or Not?",
			'orword' => "OR"

		);

		add_action('the_content', array($this,'addContent'));
		add_action('the_excerpt', array($this, 'zhn_disablePlugin'));
		add_action('admin_menu', array($this, 'addMenu'));
		add_action( 'admin_init', array($this, 'registerSettings'));

		add_action( 'wp_ajax_zhn_react', array($this,'react'));
		add_action( 'wp_ajax_nopriv_zhn_react', array($this,'react' ));
		add_action('wp_enqueue_scripts', array($this,'addStylesAndScripts'));
		add_action( 'load-post.php', array($this, 'initMetaBox'));
		add_action( 'load-post-new.php', array($this, 'initMetaBox'));
		add_shortcode( 'zuzu_hot_not', array($this, 'shortCode') );
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array($this, 'addSettingsLink' ));
	}



	function addSettingsLink ( $links ) {
		$link = array('<a href="' . admin_url( 'options-general.php?page=zhn_options' ) . '">Settings</a>');
		return array_merge( $links, $link );
	}
	function initMetaBox() {
		add_action( 'add_meta_boxes', array($this, 'addMetaBox'));
		add_action( 'save_post', array($this, 'savePostMeta'), 10, 2 );
	}

	function savePostMeta($post_id, $post) {
		if ( !isset( $_POST['zhn_enable_meta_nonce'] ) || !wp_verify_nonce( $_POST['zhn_enable_meta_nonce'], basename( __FILE__ ) ) )
		return $post_id;

		$post_type = get_post_type_object( $post->post_type );

		if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

		$meta_value = ( isset( $_POST['zhn_enable'] ) ? sanitize_html_class( $_POST['zhn_enable'] ) : '' );
		if (empty($meta_value)) {
			$meta_value = "off";
		}
		update_post_meta( $post_id, 'zhn_enable', $meta_value );
	}

	function addMetaBox() {
		add_meta_box('zhn-enable-on-post', 'Zuzu Hot or Not', array($this, 'renderMetaBox'), 'post', 'normal', 'default');
	}

	function renderMetaBox() {
		$options = get_option( 'zhn_settings' );
		$enable = isset($options['zhn_auto_enable']) ? $options['zhn_auto_enable']: 'on';
		$post_id = get_the_ID();
		$meta_enable = get_post_meta( $post_id, 'zhn_enable', true );
		if (!empty($meta_enable)) {
			$enable = $meta_enable;
		}
		wp_nonce_field( basename( __FILE__ ), 'zhn_enable_meta_nonce' );
		?>

		<label><input type="checkbox" name="zhn_enable" id="zhn-enable" <?php checked($enable, 'on')?>>Enable Hot or Not on this post</label>
		<?php
	}

	function addMenu() {
		add_options_page('Zuzu Hot or Not Settings', 'Zuzu Hot or Not', 'manage_options', 'zhn_options', array($this, 'renderOptionsPage'));
	}

	function registerSettings() {
		register_setting('zhn_options', 'zhn_settings');
	    add_settings_section( 'zhn_enable', '', array($this, 'renderEnableGuide'), 'zhn_options' );
	    add_settings_field(	'zhn_options-auto-enable-on',	'Show buttons on posts', array($this, 'renderRadio'), 'zhn_options', 'zhn_enable', array('value' => 'on'));
	    add_settings_field(	'zhn_options-auto-enable-off',	"Don't show buttons on posts", array($this, 'renderRadio'), 'zhn_options', 'zhn_enable', array('value' => 'off'));
	    add_settings_section( 'zhn_content', '', array($this, 'renderContent'), 'zhn_options' );
	    add_settings_section( 'zhn_share_translations', 'Box title', array($this, 'renderDecisionTranslations'), 'zhn_options');
	    add_settings_field(	'zhn_options-boxtitle',	'Box title', array($this, 'renderField'), 'zhn_options', 'zhn_share_translations', array('label' => 'boxtitle'));
		add_settings_section( 'zhn_translations', 'Buttons Text', array($this, 'renderDecisionTranslations'), 'zhn_options' );
	    add_settings_field(	'zhn_options-hot', 'Hot', array($this, 'renderField'), 'zhn_options', 'zhn_translations', array('label' => 'hot'));
	    add_settings_field(	'zhn_options-orword', 'Or', array($this, 'renderField'), 'zhn_options', 'zhn_translations', array('label' => 'orword'));
	    add_settings_field(	'zhn_options-not',	'Not', array($this, 'renderField'), 'zhn_options', 'zhn_translations', array('label' => 'not'));



	}


	function shortCode() {
		$options = get_option('zhn_settings');
		return $this->renderPlugin($options);
	}

	function renderField($args) {
		$label = $args['label'];
		$options = get_option('zhn_settings');
		$value = isset($options['zhn_'.$label]) ? $options['zhn_'.$label]: $this->defaults[$label];
		echo "<input type='text' name='zhn_settings[zhn_$label]' value='".esc_attr($value)."'>";
	}

	function renderContent() {
		?>

			<div style="border-top: 1px solid #bbb; width: 100%; padding: 30px 0; margin: 30px 0; border-bottom: 1px solid #bbb;">

				<h3>Adding buttons manually (short code)</h3>
				<ol>
					<li>You can use shortcode <code>[zuzu_hot_not]</code> within post or page text.</li>
					<li>You can add <code>if (function_exists('zuzu_hot_not')) { zuzu_hot_not() }</code> into your templates.</li>
				</ol>

			</div>


		<?php
	}

	function zhn_disablePlugin($excerpt) {
		$pattern = '/zhn.*/i';
		return preg_replace($pattern, '', $excerpt);
	}


	function renderRadio($args) {
		$options = get_option( 'zhn_settings' );
		$value = $args['value'];
		$set_value = isset($options['zhn_auto_enable']) ? $options['zhn_auto_enable']: 'on';
		?>
		<input type='radio' name='zhn_settings[zhn_auto_enable]' <?php checked( $set_value, $value ); ?> value='<?php echo $value ?>'>
		<?php
	}

 	function renderDecisionTranslations() {
 		echo "";
 	}

 	function renderEnableGuide() {
 		?>


 		<?php
 	}

	function renderOptionsPage() {
		?>

		<form action='options.php' method='post'>

			<p><h1>Zuzu Hot or Not Settings</h1></p><br />

			<p>Select the default setting for Zuzu Hot or Not visibility. You can override this setting for each post in the post editor.</p>

			<?php
			settings_fields( 'zhn_options' );

			do_settings_sections( 'zhn_options' );
			?>


			<?php submit_button(); ?>

		</form>

		<?php
	}


	function addContent($content) {
		$options = get_option('zhn_settings');
		$show_on_every_post = isset($options['zhn_auto_enable']) ? $options['zhn_auto_enable'] : 'on';
		$post_id = get_the_ID();
		$enabled = get_post_meta( $post_id, 'zhn_enable', true );
		if (!is_page() && ($enabled=="on" || (empty($enabled) && $show_on_every_post=='on'))) {
			$plugin = $this->renderPlugin($options);
			$content .= $plugin;
		}
		return $content;
	}

	function renderPlugin($options) {
		$post_id = get_the_ID();
		$post_url = get_permalink($post_id);
		$label_hot =isset($options['zhn_hot']) ? $options['zhn_hot']: $this->defaults['hot'];
		$label_not =isset($options['zhn_not']) ? $options['zhn_not']: $this->defaults['not'];
		$label_boxtitle =isset($options['zhn_boxtitle']) ? $options['zhn_boxtitle']: $this->defaults['boxtitle'];
		$label_orword =isset($options['zhn_orword']) ? $options['zhn_orword']: $this->defaults['orword'];



		ob_start() ?>
			<div id="zuzu_hot_not">
				<span style="display:none">zhn</span>
				<div class="zhn-decision-title"><?php echo $label_boxtitle ?></div>
			    <ul data-post-id="<?php echo $post_id ?>">
			      <li class="zuzu-hot" data-decision="hot" <?php echo $this->getClass("hot", $post_id) ?> ><a href="javascript:void(0)"><em><?php echo $label_hot ?></em><span><?php echo $this->getAmount("hot",$post_id) ?></span></a></li>
			      <p class="zuzu-or-word"><?php echo $label_orword ?></p>
			      <li class="zuzu-not" data-decision="not" <?php echo $this->getClass("not", $post_id) ?> ><a href="javascript:void(0)"><em><?php echo $label_not ?></em><span><?php echo $this->getAmount("not",$post_id) ?></span></a></li>
			    </ul>

			    <div style="clear: both;"></div>
			 </div>

		<?php
		$plugin = ob_get_contents();
		ob_clean();
		return $plugin;
	}

	function getClass($decision, $post_id) {

		$clicked = isset($_COOKIE["zhn_reacted_".$decision."_".$post_id]);

		return ($clicked ? 'class="clicked"':'');
	}

	function getAmount($decision, $post_id) {
		$meta_key = "zhn_decision_".$decision;
		$amount = get_post_meta($post_id, $meta_key, true) ? get_post_meta($post_id, $meta_key, true) : 0;
		return $amount;
	}

	function react() {
		if (isset($_POST["postid"])) {
			$post_id = $_POST["postid"];
			$decision = $_POST["decision"];
			$unreact = $_POST["unreact"];
		}
	 	$amount = $this->getAmount($decision, $post_id);
		if (isset($unreact) && $unreact === "true") {
			unset($_COOKIE['zhn_reacted_'.$decision.'_'.$post_id]);
	    	setcookie('zhn_reacted_'.$decision.'_'.$post_id, '', time() - 3600, "/");
			$amount = (int) $amount - 1;
			if ($amount >=0) {
				echo "Amount: ".$amount." ";
				update_post_meta($post_id, "zhn_decision_".$decision, $amount);
			}
		}
		else {
			setcookie('zhn_reacted_'.$decision.'_'.$post_id, $decision, time() + (86400 * 30), "/");
			$amount = (int) $amount + 1;
			if ($amount >=0) {
				echo "Amount: ".$amount." ";
				update_post_meta($post_id, "zhn_decision_".$decision, $amount);
			}
		}
		return;
	}

	function addStylesAndScripts() {
		wp_enqueue_style( 'zhn-font', 'https://fonts.googleapis.com/css?family=Roboto' );
		wp_enqueue_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
		wp_enqueue_style( 'zhn-style', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/css/zhn-styles.css', array(), "1.0.3" );
		wp_enqueue_script( 'zhn-script', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/js/zhn-script.js', array( 'jquery' ), "1.0.3" );
		$localize = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		);

		wp_localize_script( 'zhn-script', 'zhn_data', $localize );
	}

}

function zuzu_hot_not() {
	// Call from templates
	// if (function_exists('zuzu_hot_not')) { zuzu_hot_not() }
	$zhn = new Zuzu_Hot_Not();
	$options = get_option('zhn_settings');
	echo $zhn->renderPlugin($options);
}

new Zuzu_Hot_Not();