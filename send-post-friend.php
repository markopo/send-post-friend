<?php
/**
 * Plugin Name: Send post to a friend
 * Plugin URI: https://github.com/markopo
 * Description: This plugin allow you to send (email) it to a friend.
 * Version: 1.0.0
 * Author: Marko Poikkimäki
 * Author URI: https://github.com/markopo
 * License: GPL3
 */



/**
 * Class SendPostFriend
 */
class SendPostFriend {

    CONST VERSION = "1.0";
    private $spf_script = "spf-script";
    private $spf_style = "spf-style";
    private $spf_ajax = "spf_ajax";
    private $spf_nonce = "spf-nonce";



    public function init() {

        // REGISTER
        register_activation_hook(__FILE__, array($this, 'create_send_post_friend_table'));

        // LOAD
        add_action('plugins_loaded', array($this, 'enqueue_spf_scripts'));
        add_action('plugins_loaded', array($this, 'enqueue_spf_styles'));

        // REGISTER FILTER
        add_filter('the_content', array($this, 'spf_add_to_the_content'));

        // ADD ACTIONS
        add_action('admin_init', array($this,'spf_settings_init'));
        add_action('wp_ajax_spf_add_ajax_post', array($this, 'spf_add_ajax_post'));


        // REGISTER JS & CSS
        wp_register_script($this->spf_script, plugins_url('js/send-post-friend.js', __FILE__), array('jquery'), null, true);
        wp_register_style($this->spf_style, plugins_url('css/send-post-friend.css', __FILE__));
    }


    public function create_send_post_friend_table() {
        global $wpdb;
        $tablename = $wpdb->prefix . "send_post_friend";
        $wpdb_collate = $wpdb->collate;
        $sql = "CREATE TABLE `{$tablename}` (
                  `id` INT NOT NULL AUTO_INCREMENT,
                  `post_id` INT NOT NULL, 
                  `email` VARCHAR(45) NOT NULL,
                  `message` TEXT NULL,
                  `created` DATETIME NOT NULL,
                  PRIMARY KEY (`id`))
                  COLLATE {$wpdb_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }


    public function spf_settings_init(){
         add_settings_section('spf_setting_section', 'Send Post Friend Settings', array($this,'spf_setting_section'), 'general');
    }

    public function spf_setting_section() {
        global $wpdb;

        echo "<p>View the Send Post to Friends sent notifications.</p>";

        $tablename = $wpdb->prefix . "send_post_friend";
        $selectSql = "select id, post_id, email, created from {$tablename};";
        $results = $wpdb->get_results($selectSql);

        echo "<table class='widefat fixed spf-settings-table' cellspacing='0' >
               <thead>
                 <tr>
                   <th>id</th>
                   <th>post id</th>
                   <th>email</th>
                   <th>created</th>  
                 </tr>   
               </thead>
               <tbody>";

        foreach ($results as $r) { ?>
            <tr>
                <td><?php echo $r->id; ?></td>
                <td><?php echo "<a href='". get_post_permalink($r->post_id) . "' >post ({$r->post_id})</a>" ; ?></td>
                <td><?php echo $r->email; ?></td>
                <td><?php echo $r->created; ?></td>
            </tr>
        <?php
        }
        echo "</tbody>
        </table>";
    }



    public function enqueue_spf_scripts() {
        wp_enqueue_script($this->spf_script);
        $admin_url = admin_url('admin-ajax.php');
        $check_nonce = wp_create_nonce($this->spf_nonce);
        wp_localize_script($this->spf_script, $this->spf_ajax, array('ajax_url' => $admin_url, 'check_nonce' => $check_nonce));
    }

    public function enqueue_spf_styles() {
        wp_enqueue_style($this->spf_style);
    }

    public function spf_add_to_the_content($content) {

        if(get_post_type() == 'post' &&  is_user_logged_in()) {
            $spf_post_id = get_the_ID();
            $html = "<ul class='send-post-friend'  >
                        <li><label for='spf_email_text' >Send to friend:</label></li>
                        <li><input required='required' type='text' placeholder='email' name='spf_email_text_{$spf_post_id}' id='spf_email_text_{$spf_post_id}' /></li>      
                        <li><textarea id='spf_message_{$spf_post_id}' name='spf_message_{$spf_post_id}' ></textarea></li>
                        <li><button data-spf-post-id='{$spf_post_id}' class='spf_button' >Send to friend</button></li>
                     </ul>";
            $content .= $html;
        }

        return $content;
    }

    public function spf_add_ajax_post(){
        global $wpdb;

        check_ajax_referer($this->spf_nonce, 'security');
        $spf_post_id = $_POST["post_id"];
        $spf_email = $_POST["email"];
        $spf_message = $_POST["message"];

    //    echo var_dump(array($spf_post_id, $spf_email, $spf_message));

        if(!isset($spf_post_id) && is_numeric($spf_post_id)) {
            return;
        }

        if(!isset($spf_email) && !is_email($spf_email)) {
            return;
        }

        if(!isset($spf_message)) {
            return;
        }

        $spf_post_id = (int)$spf_post_id;
        $spf_email = sanitize_text_field($spf_email);
        $spf_message = sanitize_text_field($spf_message);

        $tablename = $wpdb->prefix . "send_post_friend";

        $checkSql = "select count(id) as num
                     from {$tablename}
                     where email = '{$spf_email}' and post_id = {$spf_post_id};";
        $hasAlready = $wpdb->get_var($checkSql);

        if($hasAlready == 0) {
            $insertSql = "insert into {$tablename}(post_id,email,message,created) 
                      values({$spf_post_id},'{$spf_email}','{$spf_message}',now());";
            $wpdb->query($insertSql);

            wp_mail($spf_email, 'post to friend', $spf_message);
        }

        $selectSql = "select id, email from {$tablename} 
                      where post_id = {$spf_post_id}
                      order by id desc 
                      limit 1;";
        $results = $wpdb->get_results($selectSql);
        ?>
        <?php echo json_encode($results); ?>
        <?php
         die();
    }

}

/**
 * Init plugin
 */
$spf = new SendPostFriend();
$spf->init();


