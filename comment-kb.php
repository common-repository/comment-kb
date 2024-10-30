<?php
/*
Plugin Name: Comment for Facebook KB
Plugin URI: https://www.wordpress.org/plugins/comment-kb
Description: Simple facebook comment plugin.
Author: Kbabhishek4
Author URI: https://kbabhishek4.wordpress.com/
Text Domain: cfkb
Domain Path: /languages/
Version: 1.0
*/

class Cfkb {
  private $plugin_name;
  private $plugin_code;
  private $text_domain;

  public function __construct() {
    $this->plugin_code = 'cfkb';
    $this->text_domain = 'cfkb';
    $this->plugin_name = __('Facebook Comment', $this->text_domain);

    add_action('admin_menu', array($this, 'settingPage'));
    add_action('init', array($this, 'shortcodesInit'));
    add_action('admin_init', array($this, 'setupOptions'));
    add_action('wp_head', array($this, 'addFacebookMeta'), 1);
  }

  public function settingPage() {
    add_menu_page(
        $this->plugin_name . __(' by KB', $this->text_domain),
        $this->plugin_name,
        'manage_options',
        $this->plugin_code,
        array($this, 'settingPageContent'),
        'dashicons-admin-comments',
        20
    );
  }

  public function settingPageContent() {
    if (!current_user_can('manage_options')) {
      return;
    }

    if (isset($_GET['settings-updated'])) {
      if (empty(get_option($this->plugin_code . '_api'))) {
        add_settings_error($this->plugin_code . '_messages', $this->plugin_code . '_message', __('API Code is required', $this->text_domain), 'error');
      } else {
        add_settings_error($this->plugin_code . '_messages', $this->plugin_code . '_message', __('Settings Saved', $this->text_domain), 'updated');
      }
    }

    settings_errors('cfkb_messages');

    ?>
    <div class="wrap">
      <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
      <form action="options.php" method="post">
        <?php
        settings_fields($this->plugin_code);
        do_settings_sections($this->plugin_code);
        submit_button(__('Save Settings', $this->text_domain));
        ?>
      </form>
    </div>
    <?php
  }

  public function shortcodesInit() {
    add_shortcode($this->plugin_code, array($this, 'shortcode'));
  }

  public function shortcode($atts = [], $content = null, $tag = '') {
    $app_id = get_option($this->plugin_code . '_api');
    $o = '';

    if (get_option($this->plugin_code . '_status') && $app_id) {
      $o = '<div class="fckb-wrapper">';
      $o .= isset($atts['title']) ? '<h2>' . esc_html__($atts['title'], $this->text_domain) . '</h2>' : '';

      if (!is_null($content)) {
        $o .= '<div id="fb-root"></div>
              <script>(function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s); js.id = id;
                js.src = "//connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v2.9";
                fjs.parentNode.insertBefore(js, fjs);
              }(document, \'script\', \'facebook-jssdk\'));</script>';

        $o .= '<div class="fb-comments" data-width="100%" data-href="' . get_permalink() . '" data-numposts="10"></div>';
        $o .= apply_filters('the_content', $content);
        $o .= do_shortcode($content);
      }

      $o .= '</div>';
    }

    return $o;
  }

  public function setupOptions() {
    add_settings_section(
        $this->plugin_code . '_section',
        __('Facebook API Details', $this->text_domain),
        array($this, 'sections'),
        $this->plugin_code
    );

    $fields = array(
      array(
        'uid'           => $this->plugin_code . '_api',
        'label'         => 'Fb APP ID',
        'section'       => $this->plugin_code . '_section',
        'type'          => 'text',
        'placeholder'   => 'Facebook APP ID',
        'helper'        => 'Goto https://developers.facebook.com/ register your app get APP ID',
        'supplimental'  => 'Insert here!',
      ),
      array(
        'uid'            => $this->plugin_code . '_status',
        'label'          => 'Status',
        'section'        => $this->plugin_code . '_section',
        'type'           => 'select',
        'options'        => array(
          'Disabled',
          'Enabled',
        ),
        'default'       => array()
      )
    );

    foreach ($fields as $field) {
      add_settings_field($field['uid'], $field['label'], array($this, 'fields'), $this->plugin_code, $field['section'], $field);
      register_setting($this->plugin_code, $field['uid'] );
    }
  }

  public function addFacebookMeta() {
    $app_id = get_option($this->plugin_code . '_api');

    if ($app_id && get_option($this->plugin_code . '_status')) {
      echo '<meta property="fb:app_id" content="' . $app_id . '" />';
    }
  }

  public function sections($args) {
    ?>
    <p id="<?php echo esc_attr($args['id']); ?>"><?php esc_html_e('Copy this shortcode and paste it into your post, page, or text widget content', $this->text_domain); ?></p>
    <input type="text" id="<?php echo $this->plugin_code; ?>-shortcode" onfocus="this.select();" onclick="this.select();" readonly="readonly" value="[<?php echo $this->plugin_code; ?>]" style="border:0; background: #0085ba; color: #fff; text-align: center; box-shadow: none; outline: none;font-size:1.3em;letter-spacing:1px;">
    <?php
  }

  public function fields($args) {
    $value = get_option($args['uid']);

    if (!$value) {
      $value = isset($args['default']) ? $args['default'] : '';
    }

    switch ($args['type']) {
      case 'text':
      case 'password':
      case 'number':
        printf('<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $args['uid'], $args['type'], $args['placeholder'], $value);
        break;
      case 'textarea':
        printf('<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $args['uid'], $args['placeholder'], $value);
        break;
      case 'select':
      case 'multiselect':
        if (!empty($args['options']) && is_array($args['options'])) {
          $attributes = '';
          $options_markup = '';

          foreach ($args['options'] as $key => $label) {
            if ($args['type'] === 'select') {
              $options_markup .= sprintf('<option value="%s" %s>%s</option>', $key, selected($value, $key, false), $label);
            } else {
              $options_markup .= sprintf('<option value="%s" %s>%s</option>', $key, selected($value[array_search($key, $value, true)], $key, false), $label);
            }
          }

          if ($args['type'] === 'multiselect') {
            $attributes = ' multiple="multiple" ';
          }

          if ($args['type'] === 'select') {
            printf('<select name="%1$s" id="%1$s" %2$s>%3$s</select>', $args['uid'], $attributes, $options_markup);
          } else {
            printf('<select name="%1$s[]" id="%1$s" %2$s>%3$s</select>', $args['uid'], $attributes, $options_markup);
          }
        }
        break;
      case 'radio':
      case 'checkbox':
        if (!empty($args['options']) && is_array($args['options'])) {
          $options_markup = '';
          $iterator = 0;

          foreach ($args['options'] as $key => $label) {
            $iterator++;
            $options_markup .= sprintf('<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>', $args['uid'], $args['type'], $key, checked($value[array_search($key, $value, true)], $key, false), $label, $iterator);
          }

          printf( '<fieldset>%s</fieldset>', $options_markup );
        }
        break;
    }

    if ($helper = $args['helper']) {
      printf('<span class="helper"> %s</span>', $helper);
    }

    if ($supplimental = $args['supplimental']) {
      printf('<p class="description">%s</p>', $supplimental);
    }
  }
}

new Cfkb();
