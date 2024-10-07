<?php
/*
Plugin Name: Prayer Times
Description: A plugin to display prayer times from a CSV file with customizable display options.
Version: 1.5
Author: Ghayur Haider
*/

// Register settings for the plugin
function prayer_times_register_settings() {
    register_setting('prayer_times_options_group', 'prayer_times_display_options');
    register_setting('prayer_times_options_group', 'prayer_times_maghrib_method');
    register_setting('prayer_times_options_group', 'prayer_times_text_options');

}
add_action('admin_init', 'prayer_times_register_settings');

// Create a settings page in the WordPress admin
function prayer_times_admin_menu() {
    add_menu_page('Prayer Times Settings', 'Prayer Times', 'manage_options', 'prayer-times-settings', 'prayer_times_settings_page', 'dashicons-admin-generic', 90);
}
add_action('admin_menu', 'prayer_times_admin_menu');

// Admin page content for settings
function prayer_times_settings_page() {
    // Fetch the current options
    $options = get_option('prayer_times_display_options');
    $maghrib_method = get_option('prayer_times_maghrib_method', 'calculated');
    $display_options = get_option('prayer_times_text_options');
    $color_options = get_option('prayer_times_color_options');
    
    ?>
    <div class="wrap">
        <h1>Prayer Times Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('prayer_times_options_group'); ?>

            <h3>Choose which prayer times to display:</h3>
            <table class="form-table">
                <?php
                $prayers = ['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Sunset', 'Maghrib', 'Isha', 'Midnight'];
                foreach ($prayers as $prayer) {
                    ?>
                    <tr valign="top">
                        <th scope="row">Display <?php echo $prayer; ?></th>
                        <td><input type="checkbox" name="prayer_times_display_options[<?php echo $prayer; ?>]" value="1" <?php checked(1, isset($options[$prayer])); ?> /></td>
                    </tr>
                    <?php
                }
                ?>
            </table>

            <h3>Choose Maghrib Calculation Method:</h3>
            <select name="prayer_times_maghrib_method">
                <option value="calculated" <?php selected($maghrib_method, 'calculated'); ?>>Calculated (Column 6)</option>
                <option value="flat" <?php selected($maghrib_method, 'flat'); ?>>Flat Conversion (Column 9)</option>
            </select>

            <h3>Customize Table Formatting:</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Font Family</th>
                    <td><input type="text" name="prayer_times_text_options[font_family]" value="<?php echo esc_attr($display_options['font_family'] ?? 'Arial'); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Font Size (px)</th>
                    <td><input type="number" name="prayer_times_text_options[font_size]" value="<?php echo esc_attr($display_options['font_size'] ?? '14'); ?>" /></td>
                </tr>
            </table>

            <h3>Customize Colors for Each Prayer Time:</h3>
            <table class="form-table">
                <?php
                foreach ($prayers as $prayer) {
                    ?>
                    <tr valign="top">
                        <th scope="row"><?php echo $prayer; ?> Color</th>
                        <td><input type="text" name="prayer_times_color_options[<?php echo $prayer; ?>_color]" value="<?php echo esc_attr($color_options[$prayer . '_color'] ?? '#000000'); ?>" class="color-picker" /></td>
                    </tr>
                    <?php
                }
                ?>
            </table>

            <h3>Customize Column Alignment:</h3>
            <table class="form-table">
                <?php
                foreach ($prayers as $prayer) {
                    $alignment = $display_options[$prayer . '_alignment'] ?? 'left'; // Default to left alignment
                    ?>
                    <tr valign="top">
                        <th scope="row"><?php echo $prayer; ?> Alignment</th>
                        <td>
                            <select name="prayer_times_text_options[<?php echo $prayer; ?>_alignment]">
                                <option value="left" <?php selected($alignment, 'left'); ?>>Left</option>
                                <option value="center" <?php selected($alignment, 'center'); ?>>Center</option>
                                <option value="right" <?php selected($alignment, 'right'); ?>>Right</option>
                            </select>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>

            <h3>Customize Heading and Subheading Formatting:</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Heading Font Family</th>
                    <td><input type="text" name="prayer_times_text_options[heading_font_family]" value="<?php echo esc_attr($display_options['heading_font_family'] ?? 'Arial'); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Heading Font Size (px)</th>
                    <td><input type="number" name="prayer_times_text_options[heading_font_size]" value="<?php echo esc_attr($display_options['heading_font_size'] ?? '24'); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Subheading Font Family</th>
                    <td><input type="text" name="prayer_times_text_options[subheading_font_family]" value="<?php echo esc_attr($display_options['subheading_font_family'] ?? 'Arial'); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Subheading Font Size (px)</th>
                    <td><input type="number" name="prayer_times_text_options[subheading_font_size]" value="<?php echo esc_attr($display_options['subheading_font_size'] ?? '16'); ?>" /></td>
                </tr>
            </table>

            <?php submit_button(); ?>
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
        // Round up for the rest
        $dt->modify('+1 minute')->setTime($dt->format('H'), $dt->format('i'), 0);
    }

    // Convert the rounded time to 12-hour format
    return $dt->format('g:i A');
}

// Function to display prayer times in a table format
function display_prayer_times_table() {
    // Path to the uploaded CSV file
    $csv_file_path = plugin_dir_path(__FILE__) . 'uploads/prayertimes.csv';

    // Get prayer times from the CSV file
    $maghrib_method = get_option('prayer_times_maghrib_method', 'calculated');
    $prayer_times = get_prayer_times($csv_file_path, $maghrib_method);

    // Get the current date in YYYY-MM-DD format
    $dat = new DateTime("now", new DateTimeZone('America/Toronto'));
    $current_date = $dat->format('Y-m-d');

    // Fetch the admin options for which times to display
    $options = get_option('prayer_times_display_options');
    $display_options = get_option('prayer_times_text_options');
    $color_options = get_option('prayer_times_color_options');

    // Apply user-defined styles for the main table content
    $table_style = 'border-collapse: collapse; width: auto; margin-left: auto; margin-right: auto; line-height: normal;';
    $font_style = 'font-family: ' . esc_attr($display_options['font_family'] ?? 'Arial') . '; font-size: ' . esc_attr($display_options['font_size'] ?? '14') . 'px;';

    // Apply user-defined styles for the heading and subheading
    $heading_font_style = 'font-family: ' . esc_attr($display_options['heading_font_family'] ?? 'Arial') . '; font-size: ' . esc_attr($display_options['heading_font_size'] ?? '24') . 'px; font-weight: bold; margin-bottom: 0.0em;';
    $subheading_font_style = 'font-family: ' . esc_attr($display_options['subheading_font_family'] ?? 'Arial') . '; font-size: ' . esc_attr($display_options['subheading_font_size'] ?? '16') . 'px;';

    // Check if prayer times exist for the current date
    if (array_key_exists($current_date, $prayer_times)) {
        $times = $prayer_times[$current_date];

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
    } else {
        echo "<p style='$font_style'>Prayer times for today are not available.</p>";
    }
}


// Shortcode to display the prayer times table
function prayer_times_table_shortcode() {
    ob_start();
    display_prayer_times_table();
    return ob_get_clean();
}
add_shortcode('prayer_times_table', 'prayer_times_table_shortcode');

// Add admin menu to upload CSV file
function prayer_times_admin_menu_upload() {
    add_submenu_page('prayer-times-settings', 'Prayer Times Upload', 'Upload CSV', 'manage_options', 'prayer-times-upload', 'prayer_times_upload_page');
}
add_action('admin_menu', 'prayer_times_admin_menu_upload');

// Admin page content to upload the CSV file
function prayer_times_upload_page() {
    ?>
    <div class="wrap">
        <h1>Upload Prayer Times CSV</h1>
        <p>The CSV file must be in the following format:</p>
        <p><strong>Column 1:</strong> Date (YYYY-MM-DD), <strong>Column 2:</strong> Fajr, <strong>Column 3:</strong> Sunrise, <strong>Column 4:</strong> Dhuhr, <strong>Column 5:</strong> Asr, <strong>Column 6:</strong> Sunset, <strong>Column 7:</strong> Maghrib (calculated), <strong>Column 8:</strong> Isha, <strong>Column 9:</strong> Midnight, <strong>Column 10:</strong> Maghrib (flat conversion)</p>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="prayer_csv_file" accept=".csv">
            <input type="submit" name="upload_csv" class="button button-primary" value="Upload CSV">
        </form>
    </div>
    <?php

    // Handle CSV file upload
    if (isset($_POST['upload_csv']) && isset($_FILES['prayer_csv_file'])) {
        $uploaded_file = $_FILES['prayer_csv_file'];
        $upload_dir = plugin_dir_path(__FILE__) . 'uploads/';

        // Ensure the uploads directory exists
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Move the uploaded file to the plugin directory
        $target_file = $upload_dir . 'prayertimes.csv';
        if (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
            echo '<div class="notice notice-success is-dismissible"><p>Prayer times CSV uploaded successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Failed to upload CSV file.</p></div>';
        }
    }
}
?>