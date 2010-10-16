<?php
/*
Plugin Name: Curate.Us
Plugin URI: http://curate.us/plugins/wordpress
Description: This plugin integrates Curate.Us into your blog. Allow your readers to easily create clips and quotes from your posts.
Version: 1.2
Author: Kate McKinley <kate@freerangecontent.com>
Author URI: http://curate.us
License: Apache License, Version 2.0
*/
?>
<?php
/*  Copyright 2010 Free Range Content, LLC
 *  Kate McKinley<kate@freerangecontent.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */
?>
<?php
if (!class_exists("CLP_WordpressPlugin")) {
    class CLP_WordpressPlugin {
        protected $adminOptionsName = 'clip_admin_options'; 
        protected $clipOptions;
        protected $clipOptionsDefault = array(
                'initialized' => 'false',
                'accept_link' => 'false',
                'clip_server' => 'curate.us',
                'appkey' => '',
                'show_in_title' => 'true',
                'show_in_content' => 'true',
                'button_url' => '',
            );
        protected $buttons = array(
            /* Solid */
            'http://curate.us/buttons/8.png' => 'background-color:white;',
            'http://curate.us/buttons/9.png' => 'background-color:white;',
            'http://curate.us/buttons/1.png' => 'background-color:white;',
            'http://curate.us/buttons/2.png' => 'background-color:white;',
            'http://curate.us/buttons/3.png' => 'background-color:white;',
            'http://curate.us/buttons/0.png' => 'background-color:white;',
            /* Dark text, transparent bg */
            'http://curate.us/buttons/4.png' => 'background-color:white;',
            'http://curate.us/buttons/6.png' => 'background-color:white;',
            /* Light text, transparent bg */
            'http://curate.us/buttons/5.png' => 'background-color:grey;',
            'http://curate.us/buttons/7.png' => 'background-color:grey;',
        );
        function CLP_WordpressPlugin() {
        }

        function resetAdminOptions() {
            update_option($this->adminOptionsName, $this->clipOptionsDefault);
        }

        function getAdminOptions() {
            $clipAdminOptions = $this->clipOptionsDefault;
            $clipOptions = get_option($this->adminOptionsName);
            if(!empty($clipOptions)) {
                foreach ($clipOptions as $key => $option) {
                    $clipAdminOptions[$key] = $option;
                }
            }
            update_option($this->adminOptionsName, $clipAdminOptions);
            return $clipAdminOptions;
        }

        function check_initialized() {
            $this->clipOptions = $this->getAdminOptions();
            return ($this->clipOptions['accept_link'] == 'true' && $this->clipOptions['initialized'] == 'true');
        }

        function init() {
            $this->getAdminOptions();
        }

        function clipHeader() {
            $this->check_initialized();
            $clipJs = 'http://'.$this->clipOptions['clip_server'].'/clipthis.js';
            echo "<!--\n";
            foreach ($this->clipOptions as $key => $option) {
                echo "   ".$key." => ".$option."\n";
            }
            echo $clipJs."\n";
            echo "-->\n";
            wp_enqueue_script('clipJs', $clipJs);
        }

        function clipButton($atts=null, $content=null, $code="") {
            if ($this->check_initialized()) {
                return "<a class='ClipThisButton' href='http://".apply_filters('esc_html', $this->clipOptions['clip_server'])."/simple/clipthis/".apply_filters('esc_html', $this->clipOptions['appkey'])."?url=".urlencode(get_permalink())."'><img title='Clip this story' alt='Clip this story' src='".apply_filters('esc_html', $this->clipOptions['button_url'])."'/></a>";
            } else {
                return '';
            }
        }

        function addToContent($content='') {
            if($this->check_initialized() && $this->clipOptions['show_in_content'] == 'true') {
                $content .= "<p>".$this->clipButton()."</p>";
            }
            return $content;
        }

        function addToTitle($title='') {
            if($this->check_initialized() && in_the_loop() && $this->clipOptions['show_in_title'] == 'true') {
                $title .= "&nbsp;" . $this->clipButton();
            }
            return $title;
        }

        // utility function to update a variable from the post
        function update_var_from_post(&$opts, $name, $novalue='', $value=null) {
            if(isset($_POST[$name])) {
                if($value == null) {
                    $opts[$name] = $_POST[$name];
                } else {
                    $opts[$name] = $value;
                }
            } else {
                $opts[$name] = $novalue;
            }
        }

        // prints the admin page for our plugin
        function printAdminPage() {
            $this->check_initialized();
            if (isset($_POST['update_clipPluginSettings'])) {
                check_admin_referer('curateus_admin_form_' . $clip_plugin);
                $this->update_var_from_post($this->clipOptions, 'appkey', '', preg_replace('/ /', '', $_POST['appkey']));
                $this->update_var_from_post($this->clipOptions, 'clip_server', 'curate.us', preg_replace('/[^[:alnum]-]/', '', $_POST['clip_server']));
                $this->update_var_from_post($this->clipOptions, 'show_in_title', 'false', 'true');
                $this->update_var_from_post($this->clipOptions, 'show_in_content', 'false', 'true');
                $this->update_var_from_post($this->clipOptions, 'accept_link', 'false', 'true');
                $prevButton = $this->clipOptions['button_url'];
                $this->update_var_from_post($this->clipOptions, 'button_url');
                $haveButton = false;
                foreach ($this->buttons as $button => $style) {
                    if($button == $this->clipOptions['button_url']) {
                        $haveButton = true;
                        break;
                    }
                }
                if (!$haveButton) {
                    $this->clipOptions['button_url'] = $prevButton;
                }
                if($this->clipOptions['appkey'] != '') {
                    $this->clipOptions['initialized'] = 'true';
                } else {
                    $this->clipOptions['initialized'] = 'false';
                }
                update_option($this->adminOptionsName, $this->clipOptions);
?>
<div class="updated"><p><strong><?php _e("Settings Updated.", "Curate.Us Plugin"); ?></strong></p></div>
<?php       
                $this->check_initialized();
            } ?>
<div class=wrap>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<?php
                if ( function_exists('wp_nonce_field') )
                    wp_nonce_field('curateus_admin_form_' . $clip_plugin);
?>
<input type=hidden name='clip_server' value="<?php _e(apply_filters('format_to_edit', $this->clipOptions['clip_server'])) ?>" />
<h2>Curate.Us Plugin</h2>
<h3>Allow Links Offsite</h3>
<p>
In order to function, the Curate.Us plugin needs to load javascript and insert links to <a href="http://<?php _e($this->clipOptions['clip_server']); ?>">curate.us</a>.<br>
<label for=accept_link>Allow?</label>&nbsp;<input type=checkbox name=accept_link value=true <?php if($this->clipOptions['accept_link'] == 'true') _e(' checked'); ?> />
</p>
<h3>API key (get from <a href="http://<?php _e(apply_filters('format_to_edit', $this->clipOptions['clip_server'])); ?>/my-curation#mysites">curate.us</a>)</h3>
<p>
<input type=text size=32 name='appkey' value="<?php _e(apply_filters('format_to_edit',$this->clipOptions['appkey']), 'clipPlugin'); ?>" />
</p>
<h3>Display &quot;Clip This&quot; Buttons</h3>
<p>
<label for='show_in_title'>on post titles</label>
<input type=checkbox name='show_in_title' value='true' <?php if($this->clipOptions['show_in_title'] == 'true') _e("checked"); ?> />
</p>
<p>
<label for='show_in_content'>on post content</label>
<input type=checkbox name='show_in_content' value='true' <?php if($this->clipOptions['show_in_content'] == 'true') _e("checked"); ?> />
</p>
<h3>Choose graphical button</h3>
<p>
<?php
                foreach ($this->buttons as $button => $style) {
                    $selected = "";
                    if($this->clipOptions['button_url']==$button) {
                        $selected = "checked";
                    }
                    _e("<table><tr><td style=".$style."><input type=radio name='button_url' value='".apply_filters('format_to_edit', $button)."' ".$selected." /><img src='".apply_filters('format_to_edit', $button)."' /></td></tr></table>");
                }
?>
</p>
<div class="Submit">
<input type="submit" name="update_clipPluginSettings" value="<?php _e('Update Settings', 'Curate.Us Plugin'); ?>" />
</div>
</div>
<?php
        } // end function printAdminPage()
    }

}

if(class_exists("CLP_WordpressPlugin")) {
    $clip_plugin = new CLP_WordpressPlugin();
}


//Initialize the admin panel
if (!function_exists("clip_adminPanel")) {
    function clip_adminPanel() {
        global $clip_plugin;
        if (!isset($clip_plugin)) {
            return;
        }
        if (function_exists('add_options_page')) {
            add_options_page('Curate.Us Plugin', 'Curate.Us Plugin', 9, basename(__FILE__), array(&$clip_plugin, 'printAdminPage'));
        }
    }   
}

// Short code for putting a clip this button anywhere on the page
if (!function_exists("clipButton")) {
    function clipButton($atts=null, $content=null, $code="") {
        global $clip_plugin;
        if (!isset($clip_plugin)) {
            return '';
        }
        return $clip_plugin->clipButton($atts, $content, $code);
    }   
}

// setup actions & filters
if(isset($clip_plugin)) {
    // actions
    add_action('curateus/curateus.php',  array(&$clip_plugin, 'init'));
    add_action('wp_head', array(&$clip_plugin, 'clipHeader'),1);
    add_action('admin_menu', 'clip_adminPanel');
    
    // filters
    add_filter('the_content' ,array(&$clip_plugin, 'addToContent'));
    add_filter('the_title' ,array(&$clip_plugin, 'addToTitle'));

    // add a shortcode for post authors to control placement
    add_shortcode('clip_button', 'clipButton');
}
?>
