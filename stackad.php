<?php

/*
Plugin Name: StackAd
Plugin URI: https://github.com/nathan-osman/StackAd-WordPress-Plugin
Description: StackAd provides an easy way to advertise open source projects on your blog with minimal effort.
Version: 1.0
Author: Nathan Osman
Author URI: http://quickmediasolutions.com/nathan-osman
License: GPL2
*/

/*
StackAd - Making it easy to display community ads on your blog.
Copyright (C) 2012  Nathan Osman

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class StackAd extends WP_Widget
{
    // Stores the API key we use for retrieving the data
    private $api_key = '8kHVE1Vby5suaGGTCUVqQA((';
    
    // Initializes the widget
    function __construct()
    {
        parent::WP_Widget('stackad', 'StackAd', array('description' => 'Stack Exchange Community Ads'));
    }
    
    // Retreives JSON data from the API
    function RetrieveJSON($site, $method, $can_be_empty=FALSE)
    {
        // Generate the final URL
        $url = "http://api.stackexchange.com/2.0$method" . ((strpos($method, '?') === FALSE)?'?':'&') .
          (($site != '')?"site=$site&":'') . "key={$this->api_key}";
        
        // First attempt to retrieve the data from the cache
        $data = get_site_transient('stackad_' . md5($url));
        if($data !== FALSE)
            return $data;
        
        // Make the request
        $data = wp_remote_retrieve_body(wp_remote_get($url));
        
        // Decode the data
        $json = json_decode($data, TRUE);
        if($json === null)
            throw new Exception(__('could not decode JSON data returned by the API.', 'stackad'));
        
        // Ensure 'error_message' is not present in the response
        if(isset($json['error_message']))
            throw new Exception("{$json['error_message']}.");
        
        // Ensure 'items' is in the response
        if(!isset($json['items']))
            throw new Exception(__('"items" missing from JSON response.', 'stackad'));
        
        // If the caller wanted to make sure items was not empty, check that now
        if(!$can_be_empty && !count($json['items']))
            throw new Exception(__('no items were returned in the response.', 'stackad'));
        
        // Cache the data - note that we use the hash of the URL since there is a limit
        // on the length of the name of the transient.
        set_site_transient('stackad_' . md5($url), $json['items'], 86400);
        
        // Return the JSON data
        return $json['items'];
    }
    
    // Retreives the data and generates the widget's HTML
    function GenerateHTML($site_domain)
    {
        // Retrieve the first question on the site's Meta with the [community-ads] tag
        $questions = $this->RetrieveJSON($site_domain, '/questions?tagged=community-ads&sort=creation&pagesize=1');
        
        // Now retrieve the answers for that question that meet the minimum crieterion
        $answers = $this->RetrieveJSON($site_domain, "/questions/{$questions[0]['question_id']}/answers?filter=!)rZRqQl25kll_DesFssj&min=6&pagesize=100", TRUE);
        
        // Make sure there are ads and then pick one at random
        if(!count($answers))
            echo __('There are currently no ads to display.', 'stackad');
        else
        {
            // Pick one at random
            $random_item = $answers[array_rand($answers)];
            
            // Extract the image URL
            if(!preg_match('/a href="(.*?)".*?img src="(.*?)"/', $random_item['body'], $matches))
                throw new Exception(__('post body did not contain an image.', 'stackad'));
            
            echo "<a href='{$matches[1]}' class='aligncenter stackad' data-score='{$random_item['score']}' data-link='{$random_item['link']}'><img src='{$matches[2]}' /></a>";
        }
    }
    
    // Displays an instance of the widget
    function widget($args, $instance)
    {
        echo $args['before_widget'];
        
        try
        {
            $this->GenerateHTML("meta.{$instance['site_domain']}");
        }
        catch(Exception $e)
        {
            echo '<code>' . __('Error: ', 'stackad') . $e->getMessage() . '</code>';
        }
        
        echo $args['after_widget'];
    }
    
    // Displays the form for customizing the widget instance
    function form($instance)
    {
        try
        {
            // Retrieve the existing values for settings
            if($instance)
            {
                $site_domain = esc_attr($instance['site_domain']);
            }
            else
            {
                $site_domain = '';
            }
            
            // Fetch all of the sites from the API (this is a rather large request - thankfully it's cached)
            $sites = $this->RetrieveJSON('', '/sites?filter=!)Qgc_bd3w)nu4p392tUdIebg&pagesize=999');
            
            echo '<p><label for="' . $this->get_field_id('site_domain') . '">' . __('Display ads from:', 'stackad');
            echo '</label><select id="' . $this->get_field_id('site_domain') . '" name="';
            echo $this->get_field_name('site_domain') . '" class="widefat">';
            
            // Display the list of sites
            foreach($sites as $site)
                if($site['site_state'] != 'linked_meta' && $site['api_site_parameter'] != 'stackapps')
                {
                    $selected = ($site_domain == $site['api_site_parameter'])?' selected="selected"':'';
                    echo "<option value='{$site['api_site_parameter']}'$selected>{$site['name']}</option>";
                }
            
            echo '</select></p>';
        }
        catch(Exception $e)
        {
            echo '<code>' . __('Error: ', 'stackad') . $e->getMessage() . '</code>';
        }
    }
}

// Register the widget
add_action('widgets_init', create_function('', 'register_widget("StackAd");'));

// Load the helper CSS + script
function load_stackad_script()
{
    // Load CSS files
    wp_register_style('stackad', plugins_url('css/stackad.css', __FILE__));
    wp_enqueue_style('stackad');
    
    // Load JS files
    wp_register_script('stackad', plugins_url('js/stackad.js', __FILE__), array('jquery'));
    wp_enqueue_script('stackad');
}

// Enqueue the script
add_action('wp_enqueue_scripts', 'load_stackad_script');

?>