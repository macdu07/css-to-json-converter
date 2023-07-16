<?php
/*
Plugin Name: CSS to JSON Converter
Description: Converts CSS files to JSON format
Version: 1.0
Author: Mauricio Correa
Author URI: https://mauriciocorread.com
*/

// Register activation hook
register_activation_hook(__FILE__, 'css_to_json_plugin_activate');

// Register deactivation hook
register_deactivation_hook(__FILE__, 'css_to_json_plugin_deactivate');

// Activation hook callback
function css_to_json_plugin_activate()
{
    // Perform activation tasks if needed
}

// Deactivation hook callback
function css_to_json_plugin_deactivate()
{
    // Perform deactivation tasks if needed
}

// Add options page
add_action('admin_menu', 'css_to_json_plugin_add_options_page');
function css_to_json_plugin_add_options_page()
{
    add_menu_page(
        'CSS to JSON Converter',
        'CSS to JSON',
        'manage_options',
        'css-to-json-plugin',
        'css_to_json_plugin_options_page',
        'dashicons-embed-generic',
        95
    );
}

// Options page callback
function css_to_json_plugin_options_page()
{

    // HTML for the options page
?>
    <div class="wrap">
        <h1>CSS to JSON Converter</h1>
        <p>Select a CSS file to convert to JSON:</p>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="css_file" accept=".css" required>
            <br><br>
            <input type="submit" name="submit" class="button button-primary" value="Convert">
        </form>

        <div style="margin-top: 10px;">
            <button class="button" onclick="location.reload()">Clear</button>
        </div>
    </div>

    <script>
        function copyToClipboard(elementId) {
            const el = document.getElementById(elementId);
            const range = document.createRange();
            range.selectNodeContents(el);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            document.execCommand("copy");
            alert("Code copied to clipboard!");
        }
    </script>
<?php

    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Check if the form was submitted
    if (isset($_POST['submit']) && isset($_FILES['css_file'])) {
        $css_file = $_FILES['css_file'];

        // Check if file upload was successful
        if ($css_file['error'] === UPLOAD_ERR_OK) {
            $file_type = $css_file['type'];

            // Check if the file is a CSS file
            if ($file_type === 'text/css') {
                $css_content = file_get_contents($css_file['tmp_name']);
                $converted_json = convert_css_to_json($css_content);

                // Generate JSON file
                $json_file = generate_json_file(json_encode($converted_json, JSON_PRETTY_PRINT), $css_file['name']);

                // Display the buttons
                echo '<div style="margin-top: 10px;">';
                echo '<button style="margin-right: 10px;" class="button button-primary" onclick="copyToClipboard(\'json-content\')">Copy Code</button>';
                echo '<a href="' . $json_file . '" download class="button button-primary">Download JSON</a>';
                echo '</div>';
                echo '<p><span style="font-weight: bold;">Converted file name:</span> ' . $json_file . '</p>';
            } else {
                echo 'Error: Invalid file type. Please upload a CSS file.';
            }
        } else {
            echo 'Error uploading CSS file.';
        }
    }
}

// Function to convert CSS to JSON
function convert_css_to_json($css_content)
{
    $classes = [];
    preg_match_all('/\.([a-zA-Z0-9_-]+)\s*\{([^}]*)\}/', $css_content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $class_name = $match[1];
        $class_settings = [];
        $settings = explode(';', $match[2]);
        foreach ($settings as $setting) {
            $parts = explode(':', $setting);
            if (count($parts) === 2) {
                $property = trim($parts[0]);
                $value = trim($parts[1]);
                $class_settings[$property] = $value;
            }
        }

        $class = [
            'id' => uniqid(),
            'name' => $class_name,
            'settings' => $class_settings
        ];
        $classes[] = $class;
    }

    return $classes;
}

// Function to generate JSON file
function generate_json_file($json_content, $css_file_name)
{
    // Remove the .css extension and add .json
    $json_file_name = str_replace(".css", ".json", $css_file_name);
    $handle = fopen($json_file_name, 'w');
    fwrite($handle, $json_content);
    fclose($handle);
    return $json_file_name;
}
?>