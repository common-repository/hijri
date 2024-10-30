<?php
/*
Plugin Name: Hijri
Plugin URI: http://khaledalhourani.com/
Description: Display Hijri and/or Gregorian dates in your blog.
Version: 1.1
Author: Khaled Al Hourani
Author URI: http://holooli.com
*/

if (!class_exists('Hijri')) {

class Hijri {

  /**
   * class constructor, init. actions and check version compatibility.
   */
  function Hijri() {
    global $wp_version;

    // Add Settings Panel
    add_action('admin_menu', array($this, 'addPanel'));

    // Update Settings on Save
    if ($_POST['action'] == 'hijri_update') {
      add_action('init', array($this, 'saveSettings'));
    }

    // Date hooks
    add_filter('the_date', array($this, 'getPostDate'));
    add_filter('get_the_time', array($this, 'getPostDate'));
    add_filter('get_the_date', array($this, 'getPostDate'));

    add_filter('get_comment_date', array($this, 'getCommentDate'));
    add_filter('comment_time', array($this, 'getCommentDate'));


    // Check WP Version
    if ($wp_version < 2.5) {
      add_action('admin_notices', array($this, 'versionWarning'));
    }
  }

  /**
   * Check that the current version is not less than 2.5
   */
  function versionWarning() {
    global $wp_version;

    echo "
      <div id='hijri-warning' class='updated fade-ff0000'>
        <p><strong>" . 
        __('Hijri plugin is only compatible with WordPress v2.5 and up. You are currently using WordPress v', 'hijri') . 
        $wp_version . 
        "</strong></p>
      </div>";
  }

  /**
   * Add action to build an hijri page
   */
  function addPanel() {
    //Add the Settings and User Panels
    add_options_page('Hijri', 'Hijri', 10, 'Hijri', array($this, 'hijriSettings'));
  }

  /**
   * Default settings for this plugin
   */
  function defaultSettings() {
    $defaults = array(
      'date' => array(
        'gregorian' => 1,
        'hijri' => 0,
      ),
      'algorithm' => 'arphp',
    );

    // Set defaults if no values exist
    if (!get_option('hijri')) {
      add_option('hijri', $defaults);
    }
    else {
      // Set the defaults
      $hijri = get_option('hijri');

      $config = FALSE;

      // Only fill empty values
      foreach($defaults as $key => $val) {
        if (!$hijri[$key]) {
          $hijri[$key] = $val;
          $config = TRUE;
        }
      }

      if ($config) {
        update_option('hijri', $hijri);
      }
    }
  }

  /**
   * Hijri page settings
   */
  function hijriSettings() {
    // Make sure defaults are set
    $this->defaultSettings();

    // Get options from option table
    $hijri = get_option('hijri');

    // Display message if any
    if ($_POST['notice']) {
      echo '<div id="message" class="updated fade"><p><strong>' . $_POST['notice'] . '</strong></p></div>';
    }
?>
  <link rel="stylesheet" href="<?php echo WP_PLUGIN_URL; ?>/hijri/css/style.css" type="text/css" />

  <div class="wrap" dir="ltr">
    <br/>
    <h2><?php _e('Hijri Settings', 'hijri') ?></h2>

    <form method="post" action="">
      <h4 class="title"><?php _e('Check the dates you would like to show in your blog', 'hijri');?></h4>
      <table class="form-table">
        <tbody>
          <tr valign="top">
            <td>
              <label class="item">
                <input type="checkbox" name="form_gregorian" value="gregorian" <?php if ($hijri['date']['gregorian']) { echo 'checked="checked"'; } ?> />
                <img src="<?php echo WP_PLUGIN_URL; ?>/hijri/img/gregorian.gif" /> <?php _e('Gregorian', 'hijri'); ?>
              </label>

              <label class="item">
                <input type="checkbox" name="form_hijri" value="hijri" <?php if ($hijri['date']['hijri']) { echo 'checked="checked"'; } ?> />
                <img src="<?php echo WP_PLUGIN_URL; ?>/hijri/img/hijri.gif" /> <?php _e('Hijri', 'hijri'); ?>
              </label>
            </td>
          </tr>
        </tbody>
      </table>

      <h4 class="title"><?php _e('Date algorithm', 'hijri'); ?>:</h4>
      <label class="link">
        <input type="radio" name="form_algorithm" value="arphp" <?php if ($hijri['algorithm'] == 'arphp') { echo 'checked="checked"'; } ?> />
        <a href="http://www.ar-php.org/"><?php _e('Ar-PHP', 'hijri'); ?></a>
      </label>
      <label class="link">
        <input type="radio" name="form_algorithm" value="kfcphq" <?php if ($hijri['algorithm'] == 'kfcphq') { echo 'checked="checked"'; } ?> />
        <a href="http://www.qurancomplex.org"><?php _e('King Fahd Complex for the Printing of the Holy Qur\'an', 'hijri'); ?></a>
      </label>

      <p class="submit"><input name="Submit" value="<?php _e('Save Changes', 'hijri');?>" type="submit" />
      <input name="action" value="hijri_update" type="hidden" />
    </form>
  </div>
<?php
  }

  /**
   * Save the new settings of hijri options
   */
  function saveSettings() {
    // Get options from option table
    $settings = array();

    foreach ($_POST as $input => $value) {
      if (strpos($input, 'form_') !== FALSE) {
        switch ($input) {
          case 'form_gregorian':
            $settings['date']['gregorian'] = TRUE;
            break;
          case 'form_hijri':
            $settings['date']['hijri'] = TRUE;
            break;
          case 'form_algorithm':
            $settings['algorithm'] = $value;
            break;
          default:
            break;
        }
      }
    }

    // Save the new settings to option table's record
    update_option('hijri', $settings);

    // Display success message
    $_POST['notice'] = __('Settings Saved', 'hijri');
  }

  /**
   * Get post date
   */
  function getPostDate($content) {
    $date_format = 'F j, Y';

    $post = get_post($post);

    // the false is GMT
    $gregorian_date = get_post_time($date_format, false, $post);
    return $this->getDate($gregorian_date);
  }

  /**
   * Get comment date
   */
  function getCommentDate($content) {
    $date_format = 'F j, Y';

    $comment = get_comment($comment, ARRAY_A);
    $gregorian_date = date($date_format, strtotime($comment['comment_date']));

    return $this->getDate($gregorian_date);
  }

  /**
   * Convert date
   */
  function getDate($gregorian_date) {
    $settings = get_option('hijri');

    $date = $gregorian_date;

    if ($settings['date']['hijri']) {
      if ($settings['algorithm'] == 'arphp') {
        require_once('Arabic.php');

        $Arabic = new Arabic('ArDate');
        $hijri_date = $Arabic->date('l dS F Y', strtotime($gregorian_date));
        $date = $hijri_date;
      }
      elseif ($settings['algorithm'] == 'kfcphq') {
        // Check if table exist or create it and import data into it
        $this->checkOrCreateTable();

        global $wpdb;

        $day = date('j', strtotime($gregorian_date));
        $month = date('n', strtotime($gregorian_date));
        $year = date('Y', strtotime($gregorian_date));

        $table = $wpdb->prefix . 'hijri';
        $hijri_date = $wpdb->get_row("SELECT * FROM $table WHERE gy = $year AND gm = $month AND gd =$day");

        $hijri_date = $this->HijriDaysMonths($hijri_date);
        $date = $hijri_date;
      }
    }

    if ($settings['date']['hijri'] && $settings['date']['gregorian']) {
      $date = $gregorian_date . ' - ' . $hijri_date;
    }

    return $date;
  }

  /**
   * Check if table `hijri` exist, and if not create it
   */
  function checkOrCreateTable() {
    global $wpdb;

    $table_name = $wpdb->prefix . "hijri";
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      // Table schema
      $sql = "CREATE TABLE " . $table_name . " (
        `hy` int(4) NOT NULL,
        `hm` int(2) NOT NULL,
        `hd` int(2) NOT NULL,
        `gy` int(4) NOT NULL,
        `gm` int(2) NOT NULL,
        `gd` int(2) NOT NULL,
        `gday` int(1) NOT NULL
      );";

      // Import data
      $sql .= file_get_contents(ABSPATH . 'wp-content/plugins/hijri/data/wp_hijri.sql');

      // Execute table creation query
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
    }
  }

  function HijriDaysMonths($date) {
    $months[1] = 'محرم';
    $months[2] = 'صفر';
    $months[3] = 'ربيع الأول';
    $months[4] = 'ربيع الثاني';
    $months[5] = 'جمادى الأولى';
    $months[6] = 'جمادى الثانية';
    $months[7] = 'رجب';
    $months[8] = 'شعبان';
    $months[9] = 'رمضان';
    $months[10] = 'شوال';
    $months[11] = 'ذو القعدة';
    $months[12] = 'ذو الحجة';

    $days[] = 'الأحد';
    $days[] = 'الاثنين';
    $days[] = 'الثلاثاء';
    $days[] = 'الأربعاء';
    $days[] = 'الخميس';
    $days[] = 'الجمعة';
    $days[] = 'السبت';

    $formatted_date = $days[$date->gday] . ' ' . $date->hd . ' ' . $months[$date->hm] . ' ' . $date->hy;

    return $formatted_date;
  }

} // End hijri class

} // End the BIG if


# Run The Plugin! DUH :\
if (class_exists('Hijri')) {
  $hijri = new Hijri();
}

?>