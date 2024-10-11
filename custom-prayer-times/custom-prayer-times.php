<?php
/*
Plugin Name: Prayer Times
Description: A plugin to display prayer times from a CSV file with customizable display options.
Version: 1.6.1
Author: Ghayur Haider
*/

// Register settings for the plugin
function prayer_times_register_settings() {
    register_setting('prayer_times_options_group', 'prayer_times_display_options');
    register_setting('prayer_times_options_group', 'prayer_times_maghrib_method');
    register_setting('prayer_times_options_group', 'prayer_times_text_options');
    register_setting('prayer_times_options_group', 'prayer_times_color_options');
    register_setting('prayer_times_options_group', 'prayer_times_monthly_display_options');
    register_setting('prayer_times_options_group', 'prayer_times_monthly_maghrib_method');
    register_setting('prayer_times_options_group', 'prayer_times_timezone'); // New setting for timezone
}
add_action('admin_init', 'prayer_times_register_settings');

// Enqueue color picker scripts
function prayer_times_admin_enqueue_scripts($hook_suffix) {
    if ($hook_suffix == 'toplevel_page_prayer-times-settings') {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('prayer-times-color-picker', plugins_url('prayer-times-color-picker.js', __FILE__), array('wp-color-picker'), false, true);
    }
}
add_action('admin_enqueue_scripts', 'prayer_times_admin_enqueue_scripts');

// Add admin menu with multiple tabs for better visibility
function prayer_times_admin_menu() {
    add_menu_page('Prayer Times Settings', 'Prayer Times', 'manage_options', 'prayer-times-settings', 'prayer_times_settings_page', 'dashicons-calendar', 90);
}
add_action('admin_menu', 'prayer_times_admin_menu');

// Admin page content with tabs
function prayer_times_settings_page() {
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general_settings';
    ?>
    <div class="wrap">
        <h2>Prayer Times Settings</h2>
        <h2 class="nav-tab-wrapper">
            <a href="?page=prayer-times-settings&tab=general_settings" class="nav-tab <?php echo $active_tab == 'general_settings' ? 'nav-tab-active' : ''; ?>">General Settings</a>
            <a href="?page=prayer-times-settings&tab=shortcodes" class="nav-tab <?php echo $active_tab == 'shortcodes' ? 'nav-tab-active' : ''; ?>">Shortcodes</a>
            <a href="?page=prayer-times-settings&tab=upload_csv" class="nav-tab <?php echo $active_tab == 'upload_csv' ? 'nav-tab-active' : ''; ?>">Upload CSV</a>
        </h2>

        <?php
        switch ($active_tab) {
            case 'general_settings':
                ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('prayer_times_options_group');
                    do_settings_sections('prayer_times_options_group');
                    prayer_times_general_settings();
                    submit_button();
                    ?>
                </form>
                <?php
                break;
            case 'shortcodes':
                prayer_times_shortcodes_settings();
                break;
            case 'upload_csv':
                prayer_times_upload_page();
                break;
        }
        ?>
    </div>
    <?php
}

// General settings tab content
function prayer_times_general_settings() {
    ?>
    <h3>General Settings</h3>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">Timezone</th>
            <td>
                <select name="prayer_times_timezone">
                    <?php
                    $current_timezone = get_option('prayer_times_timezone', 'America/Toronto');
                    foreach (timezone_identifiers_list() as $timezone) {
                        echo '<option value="' . esc_attr($timezone) . '"' . selected($current_timezone, $timezone, false) . '>' . esc_html($timezone) . '</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Maghrib Method (Daily)</th>
            <td>
                <select name="prayer_times_maghrib_method">
                    <option value="calculated" <?php selected(get_option('prayer_times_maghrib_method'), 'calculated'); ?>>Calculated</option>
                    <option value="flat" <?php selected(get_option('prayer_times_maghrib_method'), 'flat'); ?>>Flat Conversion</option>
                </select>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Maghrib Method (Monthly)</th>
            <td>
                <select name="prayer_times_monthly_maghrib_method">
                    <option value="calculated" <?php selected(get_option('prayer_times_monthly_maghrib_method'), 'calculated'); ?>>Calculated</option>
                    <option value="flat" <?php selected(get_option('prayer_times_monthly_maghrib_method'), 'flat'); ?>>Flat Conversion</option>
                </select>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Prayer Times to Display (Monthly)</th>
            <td>
                <?php
                $prayers = ['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Sunset', 'Maghrib', 'Isha', 'Midnight'];
                $monthly_options = get_option('prayer_times_monthly_display_options', []);
                foreach ($prayers as $prayer) {
                    ?>
                    <label>
                        <input type="checkbox" name="prayer_times_monthly_display_options[<?php echo $prayer; ?>]" value="1" <?php checked(1, isset($monthly_options[$prayer])); ?> />
                        Display <?php echo $prayer; ?>
                    </label><br>
                    <?php
                }
                ?>
            </td>
        </tr>
    </table>

    <h3>Daily Prayer Times Display Options</h3>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">Prayer Times to Display (Daily)</th>
            <td>
                <?php
                $daily_options = get_option('prayer_times_display_options', []);
                foreach ($prayers as $prayer) {
                    ?>
                    <label>
                        <input type="checkbox" name="prayer_times_display_options[<?php echo $prayer; ?>]" value="1" <?php checked(1, isset($daily_options[$prayer])); ?> />
                        Display <?php echo $prayer; ?>
                    </label><br>
                    <?php
                }
                ?>
            </td>
        </tr>
    </table>

    <h3>Text Options</h3>
    <table class="form-table">
        <?php $text_options = get_option('prayer_times_text_options', []); ?>
        <tr valign="top">
            <th scope="row">Font Family</th>
            <td>
                <input type="text" name="prayer_times_text_options[font_family]" value="<?php echo esc_attr($text_options['font_family'] ?? 'Arial'); ?>" />
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Font Size</th>
            <td>
                <input type="number" name="prayer_times_text_options[font_size]" value="<?php echo esc_attr($text_options['font_size'] ?? '14'); ?>" />
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Heading Font Family</th>
            <td>
                <input type="text" name="prayer_times_text_options[heading_font_family]" value="<?php echo esc_attr($text_options['heading_font_family'] ?? 'Arial'); ?>" />
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Heading Font Size</th>
            <td>
                <input type="number" name="prayer_times_text_options[heading_font_size]" value="<?php echo esc_attr($text_options['heading_font_size'] ?? '24'); ?>" />
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Subheading Font Family</th>
            <td>
                <input type="text" name="prayer_times_text_options[subheading_font_family]" value="<?php echo esc_attr($text_options['subheading_font_family'] ?? 'Arial'); ?>" />
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Subheading Font Size</th>
            <td>
                <input type="number" name="prayer_times_text_options[subheading_font_size]" value="<?php echo esc_attr($text_options['subheading_font_size'] ?? '16'); ?>" />
            </td>
        </tr>
    </table>

    <h3>Color Options</h3>
    <table class="form-table">
        <?php
        $color_options = get_option('prayer_times_color_options', []);
        foreach ($prayers as $prayer) {
            ?>
            <tr valign="top">
                <th scope="row"><?php echo $prayer; ?> Color</th>
                <td>
                    <input type="text" name="prayer_times_color_options[<?php echo $prayer; ?>_color]" value="<?php echo esc_attr($color_options[$prayer . '_color'] ?? '#000000'); ?>" class="wp-color-picker-field" data-default-color="#000000" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php echo $prayer; ?> Alignment</th>
                <td>
                    <select name="prayer_times_text_options[<?php echo $prayer; ?>_alignment]">
                        <option value="left" <?php selected($text_options[$prayer . '_alignment'] ?? 'left', 'left'); ?>>Left</option>
                        <option value="center" <?php selected($text_options[$prayer . '_alignment'] ?? 'left', 'center'); ?>>Center</option>
                        <option value="right" <?php selected($text_options[$prayer . '_alignment'] ?? 'left', 'right'); ?>>Right</option>
                    </select>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>
    <?php
}

// Shortcodes tab content
function prayer_times_shortcodes_settings() {
    ?>
    <h3>Available Shortcodes</h3>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">[prayer_times_monthly]</th>
            <td>Displays the prayer times for the specified month. Usage: [prayer_times_monthly month="January"] or [prayer_times_monthly month="all"] to display all months.</td>
        </tr>
        <tr valign="top">
            <th scope="row">[prayer_times_daily]</th>
            <td>Displays the prayer times for today with custom styles. Usage: [prayer_times_daily]</td>
        </tr>
    </table>
    <?php
}

// Admin page content to upload the CSV file
function prayer_times_upload_page() {
    // Handle CSV file upload
    if (isset($_POST['upload_csv']) && isset($_FILES['prayer_csv_file'])) {
        // Verify nonce
        if (!isset($_POST['prayer_times_upload_csv_nonce']) || !wp_verify_nonce($_POST['prayer_times_upload_csv_nonce'], 'prayer_times_upload_csv')) {
            echo '<div class="notice notice-error is-dismissible"><p>Security check failed. Please try again.</p></div>';
        } else {
            $uploaded_file = $_FILES['prayer_csv_file'];
            $upload_dir = plugin_dir_path(__FILE__) . 'uploads/';

            // Ensure the uploads directory exists
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Check if the uploaded file is a CSV
            $filetype = wp_check_filetype($uploaded_file['name']);
            if ($filetype['ext'] !== 'csv') {
                echo '<div class="notice notice-error is-dismissible"><p>Invalid file type. Please upload a CSV file.</p></div>';
            } else {
                // Move the uploaded file to the plugin directory
                $target_file = $upload_dir . 'prayertimes.csv';
                if (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
                    echo '<div class="notice notice-success is-dismissible"><p>Prayer times CSV uploaded successfully!</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>Failed to upload CSV file.</p></div>';
                }
            }
        }
    }
    ?>
    <div class="wrap">
        <h3>Upload Prayer Times CSV</h3>
        <p>The CSV file must be in the following format:</p>
        <p><strong>Column 1:</strong> Date (YYYY-MM-DD), <strong>Column 2:</strong> Fajr, <strong>Column 3:</strong> Sunrise, <strong>Column 4:</strong> Dhuhr, <strong>Column 5:</strong> Asr, <strong>Column 6:</strong> Sunset, <strong>Column 7:</strong> Maghrib (calculated), <strong>Column 8:</strong> Isha, <strong>Column 9:</strong> Midnight, <strong>Column 10:</strong> Maghrib (flat conversion)</p>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('prayer_times_upload_csv', 'prayer_times_upload_csv_nonce'); ?>
            <input type="file" name="prayer_csv_file" accept=".csv">
            <input type="submit" name="upload_csv" class="button button-primary" value="Upload CSV">
        </form>
    </div>
    <?php
}

// Function to get prayer times from the CSV file
function get_prayer_times($csv_file_path, $maghrib_method) {
    $prayer_times = [];

    // Check if the file exists
    if (!file_exists($csv_file_path)) {
        return $prayer_times;
    }

    // Open the CSV file
    if (($handle = fopen($csv_file_path, "r")) !== FALSE) {
        // Read the header row
        $header = fgetcsv($handle);

        // Loop through the rows and add data to the prayer_times array
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 10) {
                continue; // Skip rows with missing columns
            }
            $date = $data[0];
            $maghrib_time = ($maghrib_method === 'calculated') ? $data[6] : $data[9];
            $prayer_times[$date] = [
                'Fajr' => $data[1],
                'Sunrise' => $data[2],
                'Dhuhr' => $data[3],
                'Asr' => $data[4],
                'Sunset' => $data[5],
                'Maghrib' => $maghrib_time,
                'Isha' => $data[7],
                'Midnight' => $data[8],
            ];
        }

        // Close the file
        fclose($handle);
    }

    return $prayer_times;
}

// Function to round the time based on the prayer type and convert it to 12-hour format
function round_and_convert_prayer_time($time, $prayer) {
    $dt = DateTime::createFromFormat('H:i:s', $time);

    if (!$dt) {
        return $time;  // Return as-is if format doesn't match
    }

    if (in_array($prayer, ['Sunrise', 'Sunset', 'Midnight'])) {
        // Round down to the minute for Sunrise, Sunset, and Midnight
        $dt->setTime($dt->format('H'), $dt->format('i'), 0); // Remove seconds
    } else {
        // Round up only if seconds is non-zero
        if ($dt->format('s') > 0) {
            $dt->modify('+1 minute')->setTime($dt->format('H'), $dt->format('i'), 0);
        }
    }

    // Convert the rounded time to 12-hour format
    return $dt->format('g:i A');
}

// Shortcode to display the monthly prayer times table for each of the 12 months
function prayer_times_monthly_table_shortcode($atts) {
    // Get the month from the shortcode attributes (default to current month)
    $atts = shortcode_atts(array('month' => date('F')), $atts, 'prayer_times_monthly');
    $selected_month = ucfirst(strtolower($atts['month']));

    // Path to the uploaded CSV file
    $csv_file_path = plugin_dir_path(__FILE__) . 'uploads/prayertimes.csv';

    // Get prayer times from the CSV file
    $maghrib_method = get_option('prayer_times_monthly_maghrib_method', 'calculated');
    $prayer_times = get_prayer_times($csv_file_path, $maghrib_method);

    if (empty($prayer_times)) {
        return '<p>No prayer times available for the selected month.</p>';
    }

    // Generate the tables for each of the 12 months if requested
    $months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    ob_start();

    if (strtolower($selected_month) === 'all') {
        foreach ($months as $month) {
            echo generate_monthly_table($prayer_times, $month);
        }
    } else {
        echo generate_monthly_table($prayer_times, $selected_month);
    }

    return ob_get_clean();
}
add_shortcode('prayer_times_monthly', 'prayer_times_monthly_table_shortcode');

// Helper function to generate the monthly table
function generate_monthly_table($prayer_times, $selected_month) {
    // Prepare month filter
    $timezone = get_option('prayer_times_timezone', 'America/Toronto');
    $month_num = (new DateTime("first day of $selected_month", new DateTimeZone($timezone)))->format('m');
    $year = (new DateTime("now", new DateTimeZone($timezone)))->format('Y');

    // Fetch the admin options for which times to display
    $monthly_options = get_option('prayer_times_monthly_display_options', []);

    // Generate the monthly table
    $output = "<table class='prayer-times-table'>";
    $output .= "<thead><tr><th colspan='7' class='prayer-times-header'>$selected_month $year</th></tr>";
    $output .= "<tr><th>Date</th>";

    foreach ($monthly_options as $prayer => $value) {
        if ($value) {
            $output .= "<th>$prayer</th>";
        }
    }
    $output .= "</tr></thead><tbody>";

    foreach ($prayer_times as $date => $times) {
        $date_obj = DateTime::createFromFormat('Y-m-d', $date, new DateTimeZone($timezone));
        if ($date_obj && $date_obj->format('m') == $month_num) {
            $output .= "<tr>";
            $output .= "<td>" . $date_obj->format('d M') . "</td>";

            foreach ($monthly_options as $prayer => $value) {
                if ($value) {
                    $output .= "<td>" . round_and_convert_prayer_time($times[$prayer], $prayer) . "</td>";
                }
            }

            $output .= "</tr>";
        }
    }
    $output .= "</tbody></table><br>";

    return $output;
}

// Shortcode to display the daily prayer times table with custom styles
function prayer_times_daily_shortcode() {
    // Path to the uploaded CSV file
    $csv_file_path = plugin_dir_path(__FILE__) . 'uploads/prayertimes.csv';

    // Get prayer times from the CSV file
    $maghrib_method = get_option('prayer_times_maghrib_method', 'calculated');
    $prayer_times = get_prayer_times($csv_file_path, $maghrib_method);

    // Get today's date
    $timezone = get_option('prayer_times_timezone', 'America/Toronto');
    $dat = new DateTime("now", new DateTimeZone($timezone));
    $today_date = $dat->format('Y-m-d');

    if (empty($prayer_times) || !isset($prayer_times[$today_date])) {
        return '<p>No prayer times available for today.</p>';
    }

    // Fetch the admin options for which times to display
    $options = get_option('prayer_times_display_options', []);
    $display_options = get_option('prayer_times_text_options', []);
    $color_options = get_option('prayer_times_color_options', []);

    // Apply user-defined styles for the main table content
    $table_style = 'border-collapse: collapse; width: auto; margin-left: auto; margin-right: auto; line-height: normal;';
    $font_style = 'font-family: ' . esc_attr($display_options['font_family'] ?? 'Arial') . '; font-size: ' . esc_attr($display_options['font_size'] ?? '14') . 'px;';

    // Apply user-defined styles for the heading and subheading
    $heading_font_style = 'font-family: ' . esc_attr($display_options['heading_font_family'] ?? 'Arial') . '; font-size: ' . esc_attr($display_options['heading_font_size'] ?? '24') . 'px; font-weight: bold; margin-bottom: 0.0em;';
    $subheading_font_style = 'font-family: ' . esc_attr($display_options['subheading_font_family'] ?? 'Arial') . '; font-size: ' . esc_attr($display_options['subheading_font_size'] ?? '16') . 'px;';

    // Generate the daily table
    $times = $prayer_times[$today_date];
    ob_start();

    // Display the heading and subheading with user-defined styles
    echo "<h2 style='$heading_font_style'>Prayer Times</h2>";
    echo "<p style='$subheading_font_style'>for the Region of Waterloo <br>" . $dat->format('jS F Y') . "</p>";

    // Display the prayer times in a table with user-defined styles
    echo "<table style='$table_style'>";

    foreach ($times as $prayer => $time) {
        if (isset($options[$prayer]) && $options[$prayer]) {
            $color_style = 'color: ' . esc_attr($color_options[$prayer . '_color'] ?? '#000000') . ';';
            $alignment = esc_attr($display_options[$prayer . '_alignment'] ?? 'left');
            echo "<tr>";
            echo "<td style='padding: 8px; border: 1px solid #ddd; white-space: nowrap; $font_style $color_style text-align: $alignment;'>$prayer</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd; white-space: nowrap; $font_style $color_style text-align: $alignment;'>" . round_and_convert_prayer_time($time, $prayer) . "</td>";                            
            echo "</tr>";
        }
    }

    echo "</table>";

    return ob_get_clean();
}
add_shortcode('prayer_times_daily', 'prayer_times_daily_shortcode');
?>