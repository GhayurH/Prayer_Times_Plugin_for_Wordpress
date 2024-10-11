<?php
/**
 * Plugin Name: Custom Prayer Times Expanded
 * Description: An expanded version of the custom prayer times plugin with added functionalities for monthly shortcodes, improved settings page, and dynamic Python-generated times.
 * Version: 2.0
 * Author: Ghayur Haider
 */

// Enqueue necessary scripts for tabs in the settings page
function cpt_enqueue_admin_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('cpt-admin-js', plugins_url('admin.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_style('cpt-admin-css', plugins_url('admin.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'cpt_enqueue_admin_scripts');

// Register shortcodes for monthly prayer tables, today's prayer table, today's prayer time, and daily prayer time
function cpt_register_shortcodes() {
    $months = [
        'Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4, 'May' => 5, 'Jun' => 6,
        'Jul' => 7, 'Aug' => 8, 'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12
    ];

    foreach ($months as $month_name => $month_index) {
        add_shortcode("{$month_name}_table", function() use ($month_index) {
            return cpt_generate_month_table($month_index);
        });
    }

    add_shortcode('today_prayer_table', 'cpt_generate_today_prayer_table');
    add_shortcode('today_prayer_time', 'cpt_generate_today_prayer_time');
    add_shortcode('daily_prayer_time', 'cpt_generate_today_prayer_time');
}
add_action('init', 'cpt_register_shortcodes');

// Function to run Python script and get prayer times
function cpt_get_prayer_times_from_python($latitude, $longitude, $csv_file_path) {
    $python_script = plugin_dir_path(__FILE__) . 'prayertimes.py';  // Path to the Python script
    $command = escapeshellcmd("python3 $python_script $latitude $longitude $csv_file_path");
    $output = shell_exec($command);

    if ($output) {
        return json_decode($output, true);  // Assuming the Python script returns JSON
    } else {
        return null;
    }
}

// Generate today's prayer time
function cpt_generate_today_prayer_time() {
    $prayer_times = get_option('cpt_prayer_times');
    $today = date('Y-m-d');

    // Find the current month index based on today's date
    $month_index = (int) date('n');

    if (!$prayer_times || !isset($prayer_times[$month_index]) || !isset($prayer_times[$month_index][$today])) {
        return '<p>No prayer times available for today.</p>';
    }

    $times = $prayer_times[$month_index][$today];
    $output = '<table class="prayer-times-table">';
    $output .= '<tr><th>Prayer</th><th>Time</th></tr>';

    $display_order = ['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Sunset', 'Maghrib', 'Isha', 'Midnight'];
    $maghrib_type = get_option('cpt_maghrib_type_daily', 'calculated');

    foreach ($display_order as $prayer) {
        if ($prayer === 'Maghrib') {
            $maghrib_key = $maghrib_type === 'flat' ? 'Maghrib_Flat' : 'Maghrib_Calculated';
            $display_option = get_option("cpt_display_daily_{$prayer}");
            if (!empty($times[$maghrib_key]) && $display_option) {
                $output .= '<tr><td>' . esc_html($prayer) . '</td><td>' . esc_html($times[$maghrib_key]) . '</td></tr>';
            }
        } else {
            $display_option = get_option("cpt_display_daily_{$prayer}");
            if (!empty($times[$prayer]) && $display_option) {
                $output .= '<tr><td>' . esc_html($prayer) . '</td><td>' . esc_html($times[$prayer]) . '</td></tr>';
            }
        }
    }

    $output .= '</table>';
    return $output;
}

// Generate today's prayer table
function cpt_generate_today_prayer_table() {
    return cpt_generate_today_prayer_time();
}

function cpt_generate_month_table($month_index) {
    $prayer_times = get_option('cpt_prayer_times');
    if (!$prayer_times || !isset($prayer_times[$month_index])) {
        return '<p>No prayer times available for this month.</p>';
    }

    $output = '<table class="prayer-times-table">';
    $output .= '<tr><th>Date</th><th>Fajr</th><th>Sunrise</th><th>Dhuhr</th><th>Asr</th><th>Sunset</th><th>Maghrib</th><th>Isha</th><th>Midnight</th></tr>';

    $maghrib_type = get_option('cpt_maghrib_type_monthly', 'calculated');

    foreach ($prayer_times[$month_index] as $day => $times) {
        $output .= '<tr>';
        $output .= '<td>' . esc_html($day) . '</td>';
        foreach (['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Sunset', 'Maghrib', 'Isha', 'Midnight'] as $prayer) {
            if ($prayer === 'Maghrib') {
                $maghrib_key = $maghrib_type === 'flat' ? 'Maghrib_Flat' : 'Maghrib_Calculated';
                $display_option = get_option("cpt_display_monthly_{$prayer}");
                if (!empty($times[$maghrib_key]) && $display_option) {
                    $output .= '<td>' . esc_html($times[$maghrib_key]) . '</td>';
                } else {
                    $output .= '<td>-</td>';
                }
            } else {
                $display_option = get_option("cpt_display_monthly_{$prayer}");
                if (!empty($times[$prayer]) && $display_option) {
                    $output .= '<td>' . esc_html($times[$prayer]) . '</td>';
                } else {
                    $output .= '<td>-</td>';
                }
            }
        }
        $output .= '</tr>';
    }

    $output .= '</table>';
    return $output;
}

// Improved Settings Page
function cpt_register_settings() {
    register_setting('cpt_settings_group', 'cpt_coordinates');
    register_setting('cpt_settings_group', 'cpt_elevation');
    register_setting('cpt_settings_group', 'cpt_eqt_dec_csv', 'handle_file_upload');
    register_setting('cpt_settings_group', 'cpt_python_script', 'handle_file_upload');
    register_setting('cpt_settings_group', 'cpt_prayer_times');
    register_setting('cpt_settings_group', 'cpt_maghrib_type_daily');
    register_setting('cpt_settings_group', 'cpt_maghrib_type_monthly');

    foreach (['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Sunset', 'Maghrib', 'Isha', 'Midnight'] as $prayer) {
        register_setting('cpt_settings_group', "cpt_display_daily_{$prayer}");
        register_setting('cpt_settings_group', "cpt_display_monthly_{$prayer}");
    }
}
add_action('admin_init', 'cpt_register_settings');

function cpt_settings_page() {
    ?>
    <div class="wrap">
        <h1>Custom Prayer Times Settings</h1>
        <?php settings_errors(); ?>
        <h2 class="nav-tab-wrapper">
            <a href="#general" class="nav-tab nav-tab-active">General Settings</a>
            <a href="#display" class="nav-tab">Prayer Time Display</a>
            <a href="#coordinates" class="nav-tab">Coordinates & Calculation</a>
            <a href="#shortcodes" class="nav-tab">Shortcode Management</a>
        </h2>
        <form method="post" action="options.php" enctype="multipart/form-data">
            <?php settings_fields('cpt_settings_group'); ?>
            <?php do_settings_sections('cpt_settings_group'); ?>

            <div id="general" class="tab-content">
                <h3>General Settings</h3>
                <label for="cpt_general_setting_example">
                    <input type="checkbox" name="cpt_general_setting_example" value="1" <?php checked(1, get_option('cpt_general_setting_example'), true); ?> /> Enable General Setting Example
                </label><br/>
            </div>

            <div id="display" class="tab-content">
                <h3>Prayer Time Display Settings</h3>
                <h4>Daily Table</h4>
                <label for="cpt_maghrib_type_daily">Maghrib Calculation Type:</label>
                <select name="cpt_maghrib_type_daily">
                    <option value="calculated" <?php selected(get_option('cpt_maghrib_type_daily'), 'calculated'); ?>>Calculated</option>
                    <option value="flat" <?php selected(get_option('cpt_maghrib_type_daily'), 'flat'); ?>>Flat</option>
                </select>
                <br/>
                <?php foreach (['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Sunset', 'Maghrib', 'Isha', 'Midnight'] as $prayer) : ?>
                    <label for="cpt_display_daily_<?php echo strtolower($prayer); ?>">
                        <input type="checkbox" name="cpt_display_daily_<?php echo strtolower($prayer); ?>" value="1" <?php checked(1, get_option("cpt_display_daily_{$prayer}"), true); ?> /> Display <?php echo $prayer; ?>
                    </label><br/>
                <?php endforeach; ?>

                <h4>Monthly Table</h4>
                <label for="cpt_maghrib_type_monthly">Maghrib Calculation Type:</label>
                <select name="cpt_maghrib_type_monthly">
                    <option value="calculated" <?php selected(get_option('cpt_maghrib_type_monthly'), 'calculated'); ?>>Calculated</option>
                    <option value="flat" <?php selected(get_option('cpt_maghrib_type_monthly'), 'flat'); ?>>Flat</option>
                </select>
                <br/>
                <?php foreach (['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Sunset', 'Maghrib', 'Isha', 'Midnight'] as $prayer) : ?>
                    <label for="cpt_display_monthly_<?php echo strtolower($prayer); ?>">
                        <input type="checkbox" name="cpt_display_monthly_<?php echo strtolower($prayer); ?>" value="1" <?php checked(1, get_option("cpt_display_monthly_{$prayer}"), true); ?> /> Display <?php echo $prayer; ?>
                    </label><br/>
                <?php endforeach; ?>
            </div>

            <div id="coordinates" class="tab-content">
                <h3>Coordinates & Calculation</h3>
                <label for="cpt_coordinates">Enter Coordinates (Latitude, Longitude):</label>
                <input type="text" name="cpt_coordinates" value="<?php echo esc_attr(get_option('cpt_coordinates')); ?>" />
                <br/>
                <label for="cpt_elevation">Enter Elevation (in meters):</label>
                <input type="number" name="cpt_elevation" value="<?php echo esc_attr(get_option('cpt_elevation')); ?>" />
                <br/>
                <label for="cpt_eqt_dec_csv">Upload Equation of Time and Declination CSV:</label>
                <input type="file" name="cpt_eqt_dec_csv" />
                <input type="submit" name="upload_csv" value="Upload" class="button button-primary" />
                <br/>
                <label for="cpt_python_script">Upload Python Script:</label>
                <input type="file" name="cpt_python_script" />
                <input type="submit" name="upload_py" value="Upload" class="button button-primary" />
                <br/>
                <p>Upload the CSV file containing Equation of Time (EqT) and Declination (Dec) data, which will be used to calculate accurate prayer times. Also upload the Python script used for the calculations.</p>
            </div>

            <div id="shortcodes" class="tab-content">
                <h3>Shortcode Management</h3>
                <p>Shortcodes allow you to display monthly prayer tables on any page or post. Use the following shortcodes:</p>
                <ul>
                    <li><strong>[Jan_table]</strong> - Displays the prayer times for January</li>
                    <li><strong>[Feb_table]</strong> - Displays the prayer times for February</li>
                    <li><strong>[Mar_table]</strong> - Displays the prayer times for March</li>
                    <li><strong>[Apr_table]</strong> - Displays the prayer times for April</li>
                    <li><strong>[May_table]</strong> - Displays the prayer times for May</li>
                    <li><strong>[Jun_table]</strong> - Displays the prayer times for June</li>
                    <li><strong>[Jul_table]</strong> - Displays the prayer times for July</li>
                    <li><strong>[Aug_table]</strong> - Displays the prayer times for August</li>
                    <li><strong>[Sep_table]</strong> - Displays the prayer times for September</li>
                    <li><strong>[Oct_table]</strong> - Displays the prayer times for October</li>
                    <li><strong>[Nov_table]</strong> - Displays the prayer times for November</li>
                    <li><strong>[Dec_table]</strong> - Displays the prayer times for December</li>
                    <li><strong>[daily_prayer_time]</strong> - Displays the daily prayer times table</li>
                </ul>
                <p>Copy and paste these shortcodes into any page or post where you want to display the prayer times for a specific month or the daily prayer table.</p>
            </div>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function cpt_add_admin_menu() {
    add_menu_page('Custom Prayer Times', 'Prayer Times', 'manage_options', 'custom-prayer-times', 'cpt_settings_page');
}
add_action('admin_menu', 'cpt_add_admin_menu');

// Python Integration for Dynamic Prayer Times
function cpt_generate_prayer_times($latitude, $longitude, $elevation) {
    $csv_file = get_option('cpt_eqt_dec_csv');
    $python_script = get_option('cpt_python_script');
    if (!$csv_file || !$python_script) {
        error_log('CSV file or Python script not found. Please upload the files in the settings.');
        return;
    }
    $command = escapeshellcmd("python3 $python_script $latitude $longitude $csv_file $elevation");
    $output = shell_exec($command);

    if ($output) {
        if (strpos($output, 'Traceback') !== false || empty($output)) {
            error_log('Python script returned an error or no output. Check the script and input parameters.');
            return;
        }

        json_decode($output);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Python script returned invalid JSON: ' . json_last_error_msg());
            return;
        }

        $prayer_times = json_decode($output, true);
        if (is_array($prayer_times)) {
            update_option('cpt_prayer_times', $prayer_times);
        } else {
            error_log('Python script output is not in the expected format.');
        }
    } else {
        error_log('Python script did not return any output. Please check if the script is executable, the CSV file is correct, and the paths are accurate.');
    }
}

// Run Python script on settings save
function handle_file_upload($option) {
    if (!empty($_FILES['cpt_eqt_dec_csv']['tmp_name'])) {
        $uploaded_file = $_FILES['cpt_eqt_dec_csv'];
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['basedir'] . '/eqt_dec.csv';

        if (move_uploaded_file($uploaded_file['tmp_name'], $upload_path)) {
            update_option('cpt_eqt_dec_csv', $upload_path);
            add_settings_error('cpt_settings_group', 'csv_upload_success', 'CSV file uploaded successfully.', 'updated');
        } else {
            error_log('Failed to upload the Equation of Time and Declination CSV file.');
        }
    }

    if (!empty($_FILES['cpt_python_script']['tmp_name'])) {
        $uploaded_file = $_FILES['cpt_python_script'];
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['basedir'] . '/prayer_times.py';

        if (move_uploaded_file($uploaded_file['tmp_name'], $upload_path)) {
            update_option('cpt_python_script', $upload_path);
            add_settings_error('cpt_settings_group', 'py_upload_success', 'Python script uploaded successfully.', 'updated');
        } else {
            error_log('Failed to upload the Python script file.');
        }
    }

    return $option;
}

function cpt_save_coordinates($option_name, $old_value, $value) {
    if ($option_name === 'cpt_coordinates' || $option_name === 'cpt_elevation') {
        $coordinates = get_option('cpt_coordinates');
        $elevation = get_option('cpt_elevation');
        if ($coordinates) {
            list($latitude, $longitude) = explode(',', $coordinates);
            cpt_generate_prayer_times(trim($latitude), trim($longitude), intval($elevation));
        }
    }
}
add_action('updated_option', 'cpt_save_coordinates', 10, 3);