<?php
/*
  Plugin Name: WPVN - Username Changer
  Plugin URI: http://itscaro.me/read/wpvn-username-changer/
  Description: This plugin lets you <a href='users.php?page=wpvn_username_changer'>change user's login username</a>, especially useful if you need to change your admin username.
  Version: 0.7.8
  Author: Minh-Quan Tran (itscaro)
  Author URI: http://itscaro.me/donate/

  License GPL, please refer to the LICENSE file.
 */

if (is_admin() && !class_exists('ItscaroUsernameChanger') && version_compare($wp_version, '2.3.0', '>=')) {

    class ItscaroUsernameChanger {

        private $level_can_use;

        public function __construct()
        {
            load_plugin_textdomain('wpvn-username-changer', false, 'wpvn-username-changer/l10n');
            $this->level_can_use = get_option('wpvn_username_changer');
            add_action('admin_menu', array(&$this, 'wpvn_add_menu'));
        }

        public function wpvn_add_menu()
        {
            if (0 > $this->level_can_use || 10 < $this->level_can_use || !is_numeric($this->level_can_use)) {
                $this->level_can_use = 10;
            }
            add_submenu_page('users.php', __('Change Your Username', 'wpvn-username-changer'), __('Change Username', 'wpvn-username-changer'), $this->level_can_use, 'wpvn-username-changer', array(&$this, 'init'));
        }

        public function init()
        {
            global $wpdb, $userdata, $current_user;

            get_currentuserinfo();

            if (!empty($_POST['new_user_login'])) {
                $new_user_login = sanitize_user($_POST['new_user_login'], true);
                $new_user_login_check = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->users WHERE user_login = %s AND ID != %d LIMIT 1", $new_user_login, (int) $userdata->ID));
                if ($new_user_login_check) {
                    echo "<div id='message' class='error'><p><strong>" .
                    __('The entered username is used by another user, please choose a different one!', 'wpvn-username-changer')
                    . "</strong></p></div>";
                }
            } elseif (isset($_POST['level_can_use'])) {
                if (0 > $_POST['level_can_use'] || 10 < $_POST['level_can_use'] || !is_numeric($_POST['level_can_use']))
                    $_POST['level_can_use'] = 10;
                update_option('wpvn_username_changer', $_POST['level_can_use']);
                $this->level_can_use = get_option('wpvn_username_changer');
            }
            if (!empty($_POST['new_user_login']) && $_POST['new_user_login'] !== $userdata->user_login && !$new_user_login_check) {
                $new_user_nicename = sanitize_title($new_user_login);
                $new_nickname = $new_user_login;
                $new_user_nicename_check = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->users WHERE user_nicename = %s AND ID != %d LIMIT 1", $new_user_nicename, (int) $userdata->ID));
                if ($new_user_nicename_check) {
                    $suffix = 2;
                    while ($new_user_nicename_check) {
                        $alt_new_user_nicename = $new_user_nicename . "-$suffix";
                        $new_user_nicename_check = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->users WHERE user_nicename = %s AND ID != %d LIMIT 1", $alt_new_user_nicename, (int) $userdata->ID));
                        $suffix++;
                    }
                    $new_user_nicename = $alt_new_user_nicename;
                }
                $q = sprintf("UPDATE %s SET user_login='%s', user_nicename='%s', display_name='%s' WHERE ID=%d", $wpdb->users, $new_user_login, $new_user_nicename, $new_nickname, (int) $userdata->ID);
                if (false !== $wpdb->query($q)) {
                    update_usermeta($userdata->ID, 'nickname', $new_nickname);
                    get_currentuserinfo();
                    echo "<div id='message' class='updated fade'><p><strong>" .
                    sprintf(__('Your username was changed to <em>%s</em>. Please <a href="%s">log in</a> again with this username now.', 'wpvn-username-changer'), $user_login, get_option('siteurl') . '/wp-login.php')
                    . "</strong></p></div>";
                } else {
                    echo "<div id='message' class='error'><p><strong>" .
                    __('A database error occured : ', 'wpvn-username-changer') . $wpdb->last_error
                    . "</strong></p></div>";
                }
            } else {
                if ($_POST['new_user_login'] === $userdata->user_login) {
                    echo "<div id='message' class='error'><p><strong>" .
                    __('The new username is identic to the old one, so no change was made!', 'wpvn-username-changer')
                    . "</strong></p></div>";
                }
                ?>
                <div class="wrap">
                    <h2><?php _e('Change Your Username', 'wpvn-username-changer') ?></h2>
                    <form name="wpvn_username_changer" method="post">
                        <table>
                            <tr>
                                <th align="left"><label for="current_user_login"><?php _e('Current Username', 'wpvn-username-changer') ?></label></th>
                                <td><input type="text" id="current_user_login" name="current_user_login" value="<?php echo $userdata->user_login ?>" size="30" disabled="disabled" /></td>
                            </tr>
                            <tr>
                                <th align="left"><label for="new_username"><?php _e('New Username', 'wpvn-username-changer') ?></label></th>
                                <td><input type="text" id="new_user_login" name="new_user_login" value="" size="30" /></td>
                            </tr>
                        </table>
                        <p><?php _e('After clicking <strong>Save Changes</strong>, you will need to log in with your new username.', 'wpvn-username-changer') ?></p>
                        <p class="submit"><input type="submit" id="wpvn_username_changer_submit" name="wpvn_username_changer_submit" class="button" value="<?php _e('Save Changes', 'wpvn-username-changer') ?>" /></p>
                    </form>
                <?php if (current_user_can('edit_users')) { ?>
                        <hr />
                        <h2><?php _e('Settings', 'wpvn-username-changer') ?></h2>
                        <form name="wpvn_username_changer_settings" method="post">
                            <table>
                                <tr>
                                    <th align="left"><label for="level_can_use"><?php _e('Users must have at least this level to be able to change their username', 'wpvn-username-changer') ?></label></th>
                                    <td>
                                        <select type="text" id="level_can_use" name="level_can_use">
                                            <option value="10" <?php if (10 == $this->level_can_use) echo "selected" ?>><?php echo (function_exists('_x')) ? _x('Administrator', 'User role') : _e('Administrator', 'wpvn-username-changer') ?></option>
                                            <option value="7" <?php if (7 == $this->level_can_use) echo "selected" ?>><?php echo (function_exists('_x')) ? _x('Editor', 'User role') : _e('Editor', 'wpvn-username-changer'); ?></option>
                                            <option value="2" <?php if (2 == $this->level_can_use) echo "selected" ?>><?php echo (function_exists('_x')) ? _x('Author', 'User role') : _e('Author', 'wpvn-username-changer'); ?></option>
                                            <option value="1" <?php if (1 == $this->level_can_use) echo "selected" ?>><?php echo (function_exists('_x')) ? _x('Contributor', 'User role') : _e('Contributor', 'wpvn-username-changer'); ?></option>
                                            <option value="0" <?php if (0 == $this->level_can_use) echo "selected" ?>><?php echo (function_exists('_x')) ? _x('Subscriber', 'User role') : _e('Subscriber', 'wpvn-username-changer'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                            <p class="submit"><input type="submit" id="level_can_use_submit" name="level_can_use_submit" class="button" value="<?php _e('Save Changes', 'wpvn-username-changer') ?>" /></p>
                        </form>
                <?php } ?>
                </div>
                <?php
            }
        }

    }

    $itscaroUsernameChanger = new ItscaroUsernameChanger;
}