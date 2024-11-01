<?php
/**
 * TargetBay Reviews and Ecommerce Emails TargetBay Settings.
 *
 * @since   0.1.0
 * @package TargetBay_Reviews_and_Ecommerce_Emails
 */

use GuzzleHttp\Client;

/**
 * TargetBay Reviews and Ecommerce Emails TargetBay Settings class.
 *

 * @since 0.1.0
 */
class TBWC_Targetbay_Settings
{
    /**
     * Parent plugin class.
     *
     * @var    TargetBay_Reviews_and_Ecommerce_Emails
     * @since  0.1.0
     */
    protected $plugin = null;

    /**
     * Option key, and option page slug.
     *
     * @var    string
     * @since  0.1.0
     */
    protected $key = 'tb_wc_settings';

    /**
     * Options page metabox ID.
     *
     * @var    string
     * @since  0.1.0
     */
    protected $metabox_id = 'tb_wc_settings_metabox';

    protected $menu_title = '';

    /**
     * Options Page title.
     *
     * @var    string
     * @since  0.1.0
     */
    protected $title = '';

    /**
     * Options Page hook.
     *
     * @var string
     */
    protected $options_page = '';

    protected $targetBay = 'https://dev.targetbay.com';

    /**
     * Constructor.
     *
     * @since  0.1.0
     *
     * @param  TargetBay_Reviews_and_Ecommerce_Emails $plugin Main plugin object.
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->hooks();

        // Set our title.
        $this->menu_title = esc_attr__('TargetBay', 'targetbay-reviews-and-ecommerce-emails');
        $this->title = esc_attr__('TargetBay - Settings', 'targetbay-reviews-and-ecommerce-emails');
    }

    /**
     * Initiate our hooks.
     *
     * @since  0.1.0
     */
    public function hooks()
    {
        // Hook in our actions to the admin.
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'add_targetbay_options_page'));
        
    }

    /**
     * Register our setting to WP.
     *
     * @since  0.1.0
     */
    public function admin_init()
    {
        register_setting($this->key, $this->key);
    }
    /**
     * Add menu options page.
     *
     * @since  0.1.0
     */
    public function add_options_page()
    {
        $this->options_page = add_menu_page(
            $this->title,
            $this->menu_title,
            'manage_options',
            $this->key,
            array($this, 'admin_page_display')
        );

        // Include CMB CSS in the head to avoid FOUC.
        add_action("admin_print_styles-{$this->options_page}", array('CMB2_hookup', 'enqueue_cmb_css'));
    }
    /**
     * Admin page markup. Mostly handled by CMB2.
     *
     * @since  0.1.0
     */
    public function admin_page_display()
    {
        ?>
        <div class="wrap cmb2-options-page <?php echo esc_attr($this->key); ?>">
            <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
            <?php cmb2_metabox_form($this->metabox_id, $this->key); ?>
        </div>
        <?php
    }

    /**
     * Add menu options page.
     *
     * @since  0.1.0
     */
    public function add_targetbay_options_page()
    {
        $this->options_page = add_menu_page(
            $this->title,
            $this->menu_title,
            'manage_options',
            $this->key,
            array($this, 'admin_targetbay_page_display')
        );

        add_action("admin_print_styles-{$this->options_page}", array($this, 'wc_targetbay_admin_styles'));
    }

    /**
     * Admin page settings.
     *
     * @since  0.1.0
     */
    public function admin_targetbay_page_display()
    {
        if (isset($_POST['targetbay_settings'])) {
            check_admin_referer('targetbay_settings_form');
            $this->wc_proccess_targetbay_settings();
            $this->wc_display_targetbay_settings();
        }else{
            $this->wc_display_targetbay_settings();
        }
    }

    public function wc_targetbay_admin_styles($hook) {
        wp_enqueue_style( 'tbSettingsStylesheet', plugins_url('../assets/css/targetbay.css', __FILE__));
    }

    /**
     * @param bool $success_type
     */
    public function wc_display_targetbay_settings($success_type = false) {
        $tb_settings = get_option('targetbay_settings', $this->wc_targetbay_get_degault_settings());
        $tb_api_secret = $tb_settings['tb_api_secret'];
    	$tb_adroll_adv_id = $tb_settings['tb_adroll_adv_id'];
    	$tb_adroll_pix_id = $tb_settings['tb_adroll_pix_id'];
        if (empty($tb_settings['tb_api_secret'])) {
            $this->wc_targetbay_display_message('Set your API secret in order the TargetBay plugin to work correctly');
        }

        $google_tracking_params = '&utm_source=targetbay_woocommerce&utm_medium=header_link&utm_campaign=woocommerce_customize_link';
        $dashboard_link = "<a href='https://app.targetbay.com/login?$google_tracking_params' target='_blank'>TargetBay Dashboard.</a>";



        $read_only = isset($_POST['log_in_button']) || $success_type === 'b2c' ? '' : 'readonly';
        $credentials_location_explanation = isset($_POST['log_in_button']) ? "<tr valign='top'>  	
                                                                                <th>
                                                                                <p class='description'>To get your api key and secret token <a href='https://app.targetbay.com/login' target='_blank'>log in here</a> and go to your account settings.</p>
                                                                                </th>
                                                                               </tr>" : '';
        $settings_html = "<div class='wrap tb-wrap'>
                            <h2>TargetBay Settings</h2>
                            <h4>To customize the look and feel of the widget, and to edit your Mail After Purchase settings, just head to the " . $dashboard_link . "</h4>
                            <form  method='post' id='targetbay_settings_form'>" .
            wp_nonce_field('targetbay_settings_form') .
            "<table class='form-table'><tr valign='top'>
                            <th>
                            TargetBay API Server
                            </th>
                            <td>
                                <select id='tb_server' name='tb_server' class='tb-server'>
                                    <option value='dev' " . selected('dev', $tb_settings['tb_server'], false) . ">Test</option>
                                    <option value='live' " . selected('live', $tb_settings['tb_server'], false) . ">Live</option>
                                </select>
                            </td>
                         </tr>
                         
                         <tr valign='top' class='targetbay-widget-tab-name'>
                           <th>TargetBay API Secret:</th>
                           <td><input type='text' id='tb_api_secret' name='tb_api_secret' value='$tb_api_secret'></td>
                         </tr>
                         
                         <tr valign='top'>
                            <th>
                            Tracking Type
                            </th>
                            <td>
                            <select id='tb_tracking_type' name='tb_tracking_type' class='tb-tracking'>
                                <option value='back' " . selected('back', $tb_settings['tb_tracking_type'], false) . ">Backend</option>
                                <option value='front' " . selected('front', $tb_settings['tb_tracking_type'], false) . ">Frontend</option>
                            </select>
                            </td>
                         </tr>
                         
                         <tr valign='top'>
                            <th>
                            Rich Snippets
                            </th>
                            <td>
                            <select id='tb_rich_snippets' name='tb_rich_snippets' class='tb-tracking'>
                                <option value='automatic' " . selected('automatic', $tb_settings['tb_rich_snippets'], false) . ">Automatic</option>
                                <option value='manual' " . selected('manual', $tb_settings['tb_rich_snippets'], false) . ">Manual</option>
                            </select>
                            </td>
                         </tr>
                         
                         <tr valign='top'>
                            <th>
                            Product Review
                            </th>
                            <td>
                            <select id='tb_pro_review' name='tb_pro_review' class='tb-tracking'>
                                <option value='enable' " . selected('enable', $tb_settings['tb_pro_review'], false) . ">Enable</option>
                                <option value='disable' " . selected('disable', $tb_settings['tb_pro_review'], false) . ">Disable</option>
                            </select>
                            </td>
                         </tr>
                         
                         <tr valign='top'>
                            <th>
                            Bulk Review
                            </th>
                            <td>
                            <select id='tb_bulk_review' name='tb_bulk_review' class='tb-tracking'>
                                <option value='enable' " . selected('enable', $tb_settings['tb_bulk_review'], false) . ">Enable</option>
                                <option value='disable' " . selected('disable', $tb_settings['tb_bulk_review'], false) . ">Disable</option>
                            </select>
                            </td>
                         </tr>
                         <tr valign='top'>
                           <th>Disable native reviews system:</th>
                           <td>
                           <input type='checkbox' name='disable_wp_review_system' value='1' " . checked(1, $tb_settings['disable_wp_review_system'], false) . ">
                           </td>
                         </tr>
			 <tr valign='top' class='targetbay-widget-tab-name'>
                           <th>TargetBay adroll adv id:</th>
                           <td><input type='text' id='tb_adroll_adv_id' name='tb_adroll_adv_id' value='$tb_adroll_adv_id'></td>
                         </tr>
                       <tr valign='top' class='targetbay-widget-tab-name'>
                           <th>TargetBay adroll pix id:</th>
                           <td><input type='text' id='tb_adroll_pix_id' name='tb_adroll_pix_id' value='$tb_adroll_pix_id'></td>
                         </tr>
                    </table>
                    <br>
                    <div class='buttons-container'>
                        <input type='submit' id='targetbay_settings' name='targetbay_settings' value='Update' class='button-primary' id='save_targetbay_settings'>
                    </div>
                  </form>		  		  
                </div>";

        echo $settings_html;
    }

    /**
     * Save options.
     */
    public function wc_proccess_targetbay_settings() {
        $current_settings = get_option('targetbay_settings', $this->wc_targetbay_get_degault_settings());
        $new_settings = array(
            'tb_server' => $_POST['tb_server'],
            'tb_api_secret' => $_POST['tb_api_secret'],
            'tb_tracking_type' => $_POST['tb_tracking_type'],
            'tb_rich_snippets' => $_POST['tb_rich_snippets'],
            'tb_pro_review' => $_POST['tb_pro_review'],
            'tb_bulk_review' => $_POST['tb_bulk_review'],
            'tb_adroll_pix_id' => $_POST['tb_adroll_pix_id'],
            'tb_adroll_adv_id' => $_POST['tb_adroll_adv_id'],
            'disable_wp_review_system' => isset($_POST['disable_wp_review_system']) ? true : false
        );
        update_option('targetbay_settings', $new_settings);

        if ($current_settings['disable_wp_review_system'] !== $new_settings['disable_wp_review_system']) {
            if ($new_settings['disable_wp_review_system'] === false) {
                update_option('woocommerce_enable_review_rating', get_option('wp_star_ratings_enabled'));
            } else {
                update_option('woocommerce_enable_review_rating', 'no');
            }
        }
    }

    /**
     * TargetBay account registration.
     */
    public function wc_display_targetbay_register() {
        $email = isset($_POST['targetbay_user_email']) ? $_POST['targetbay_user_email'] : '';
        $user_name = isset($_POST['targetbay_user_name']) ? $_POST['targetbay_user_name'] : '';

        if($email === '') {
            $current_user = wp_get_current_user();
            $email = $current_user->user_email;
            $user_name = $current_user->display_name;
        }

        $register_html = "<div class='wrap tb-wrap'>
                    <h2>TargetBay Registration</h2>
                    <form method='post'>
                    <table class='form-table'>"
            . wp_nonce_field('targetbay_registration_form') .
            "<tr><th colspan='2'>
                          <h2 class='targetbay-register-title'>
                          Fill out the form below and Submit to get started with TargetBay.
                          </h2>
                          </th></tr>
                          <tr valign='top'>
                            <th>Email address:</th>			 			  
                            <td><input type='text' id='targetbay_user_email' name='targetbay_user_email' value='$email'></td>
                          </tr>
                          <tr valign='top'>
                            <th>Name:</th>			 			  
                            <td><input type='text' id='targetbay_user_name' name='targetbay_user_name' value='$user_name'></td>
                          </tr>
                          <tr valign='top'>
                            <th>Password:</th>			 			  
                            <td><input type='password' id='targetbay_user_password' name='targetbay_user_password'></td>
                          </tr>
                          <tr valign='top'>
                            <th>Confirm password:</th>			 			  
                            <td><input type='password' id='targetbay_user_confirm_password' name='targetbay_user_confirm_password'></td>
                          </tr>
                          <tr valign='top'>
                            <th></th>
                            <td><input type='submit' id='targetbay_register' name='targetbay_register' value='Register' class='button-primary submit-btn'></td>
                          </tr>			  
                        			
                        <table/>
                    </form>
                    <br><br>
                    
                    <form method='post'>
                        <div style='background: #fff; border-left: 4px solid #fff;border-left-color: #00a0d2; padding: 10px 12px 6px'>                     
                        Already registered to TargetBay?
                        <input type='submit' id='log_in_button' name='log_in_button' value='click here' class='button-secondary not-user-btn'>
                      </div>
                    </form>
                    </br><br>
                    
                    <div class='targetbay-terms'>
                    By registering I accept the <a href='https://targetbay.com/terms-of-service-agreement/' target='_blank'>
                    Terms of Use</a> and recognize that a 'Powered by TargetBay' link will appear on the top of 
                    my TargetBay widget.
                    </div>
              </div>";

        echo $register_html;
    }

    

    /**
     * @param array $messages
     * @param bool $is_error
     */
    public function wc_targetbay_display_message($messages = array(), $is_error = false) {
        $class = $is_error ? 'error' : 'updated fade';
        if (is_array($messages)) {
            foreach ($messages as $message) {
                echo "<div id='message' class='$class'>
                        <p><strong>$message</strong></p>
                      </div>";
            }
        } elseif (is_string($messages)) {
            echo "<div id='message' class='$class'>
                    <p><strong>$messages</strong></p>
                  </div>";
        }
    }

    /**
     * @return array
     */
    public function wc_targetbay_get_degault_settings() {
        return array(
            'tb_server' => 'live',
            'tb_api_secret' => '',
            'tb_tracking_type' => 'back',
            'tb_rich_snippets' => 'manual',
            'tb_pro_review' => 'enable',
            'tb_bulk_review' => 'enable',
            'disable_wp_review_system' => true,
            'wp_star_ratings_enabled' => 'no'
        );
    }

}