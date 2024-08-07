<?php
/**
 * Generate the opening tag for an HTML form.
 *
 * @param string $location The URL to which the form will be submitted.
 * @param array|null $attributes An optional array of HTML attributes for the form.
 * @param string|null $additional_code An optional additional code to include in the form tag.
 * @return string The HTML opening tag for the form.
 */
function form_open(string $location, ?array $attributes = null, ?string $additional_code = null): string {
    $extra = '';
    $method = 'post';

    if (is_array($attributes)) {
        if (isset($attributes['method'])) {
            $method = $attributes['method'];
            unset($attributes['method']);
        }
        foreach ($attributes as $key => $value) {
            $extra .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
    }

    if (!filter_var($location, FILTER_VALIDATE_URL) && strpos($location, '/') !== 0) {
        $location = BASE_URL . $location;
    }

    if ($additional_code !== null) {
        $extra .= ' ' . htmlspecialchars($additional_code, ENT_QUOTES, 'UTF-8');
    }

    return '<form action="' . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . '" method="' . htmlspecialchars($method, ENT_QUOTES, 'UTF-8') . '"' . $extra . '>';
}

/**
 * Generate the opening tag for an HTML form with file upload support.
 *
 * @param string $location The URL to which the form will be submitted.
 * @param array|null $attributes An optional array of HTML attributes for the form.
 * @param string|null $additional_code An optional additional code to include in the form tag.
 * @return string The HTML opening tag for the form with enctype set to "multipart/form-data."
 */
function form_open_upload(string $location, ?array $attributes = null, ?string $additional_code = null): string {
    $attributes = is_array($attributes) ? $attributes : [];
    $attributes['enctype'] = 'multipart/form-data';
    return form_open($location, $attributes, $additional_code);
}

/**
 * Generate the closing tag for an HTML form, including CSRF token and inline validation errors (if any).
 *
 * @return string The HTML closing tag for the form.
 */
function form_close(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $html = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
    $html .= '</form>';

    if (isset($_SESSION['form_submission_errors'])) {
        $errors_json = json_encode($_SESSION['form_submission_errors'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $html .= highlight_validation_errors($errors_json);
        unset($_SESSION['form_submission_errors']);
    }

    return $html;
}

/**
 * Highlight validation errors using provided JSON data.
 *
 * @param string $errors_json JSON data containing validation errors.
 * @return string HTML code for highlighting validation errors.
 */
function highlight_validation_errors(string $errors_json): string {
    $output_str = file_get_contents(APPPATH . 'engine/views/highlight_errors.txt');
    if ($output_str === false) {
        error_log('Failed to read highlight_errors.txt file');
        return '';
    }
    return '<div class="inline-validation-builder"><script>let validationErrorsJson = ' . $errors_json . ';</script><script>' . $output_str . '</script></div>';
}

/**
 * Get a string representation of HTML attributes from an associative array.
 *
 * @param array|null $attributes An associative array of HTML attributes.
 * @return string A string representation of HTML attributes.
 */
function get_attributes_str($attributes): string {
    if (!is_array($attributes) || empty($attributes)) {
        return '';
    }

    $attributes_str = '';
    foreach ($attributes as $key => $value) {
        if ($value === null || $value === false) {
            continue;
        }
        if ($value === true) {
            $attributes_str .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        } else {
            $attributes_str .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
    }
    return $attributes_str;
}

/**
 * Generate an HTML label element with optional attributes.
 *
 * @param string $label_text The text to display inside the label.
 * @param array|null $attributes An associative array of HTML attributes for the label.
 * @param string|null $additional_code Additional HTML code to append to the label element.
 * @return string The generated HTML label element.
 */
function form_label($label_text, $attributes = null, $additional_code = null): string {
    $extra = get_attributes_str($attributes);
    
    if ($additional_code !== null) {
        $extra .= ' ' . $additional_code;
    }

    return '<label' . $extra . '>' . htmlspecialchars($label_text, ENT_QUOTES, 'UTF-8') . '</label>';
}

/**
 * Generate an HTML input element.
 *
 * @param string $name The name attribute for the input element.
 * @param mixed $value (optional) The initial value for the input element. Default is null.
 * @param array|null $attributes (optional) Additional attributes for the input element. Default is null.
 * @param string|null $additional_code (optional) Additional code to include in the input element. Default is null.
 *
 * @return string The HTML representation of the input element.
 */
function form_input(string $name, $value = null, ?array $attributes = null, ?string $additional_code = null): string {
    $attributes = $attributes ?? [];
    $attributes['name'] = $name;
    $attributes['type'] = $attributes['type'] ?? 'text';
    
    if ($value !== null) {
        $attributes['value'] = $value;
    }

    $html = '<input' . get_attributes_str($attributes);
    
    if ($additional_code !== null) {
        $html .= ' ' . $additional_code;
    }

    return $html . '>';
}

/**
 * Generate an HTML search input element.
 *
 * @param string $name The name attribute for the search input element.
 * @param mixed $value (optional) The initial value for the search input element. Default is null.
 * @param array|null $attributes (optional) Additional attributes for the search input element. Default is null.
 * @param string|null $additional_code (optional) Additional code to include in the search input element. Default is null.
 *
 * @return string The HTML representation of the search input element.
 */
function form_search(string $name, $value = null, ?array $attributes = null, ?string $additional_code = null): string {
    $attributes = $attributes ?? [];
    $attributes['type'] = 'search';
    return form_input($name, $value, $attributes, $additional_code);
}

/**
 * Generate an HTML input element with type "number".
 *
 * @param string $name The name attribute for the input element.
 * @param int|float|string|null $value The initial value of the input element.
 * @param array|null $attributes An associative array of HTML attributes for the input.
 * @param string|null $additional_code Additional HTML code to append to the input element.
 * @return string The generated HTML input element with type "number".
 */
function form_number(string $name, $value = null, ?array $attributes = null, ?string $additional_code = null): string {
    $attributes = $attributes ?? [];
    $attributes['type'] = 'number';
    return form_input($name, $value, $attributes, $additional_code);
}

/**
 * Generate an HTML input element with type "password".
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The initial value of the input element (password). Use null for no initial value.
 * @param array|null $attributes An associative array of HTML attributes for the input.
 * @param string|null $additional_code Additional HTML code to append to the input element.
 * @return string The generated HTML input element with type "password".
 */
function form_password(string $name, ?string $value = null, ?array $attributes = null, ?string $additional_code = null): string {
    $attributes = $attributes ?? [];
    $attributes['type'] = 'password';
    return form_input($name, $value, $attributes, $additional_code);
}

/**
 * Generate an HTML input element with type "email".
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The initial value of the input element (email). Use null for no initial value.
 * @param array|null $attributes An associative array of HTML attributes for the input.
 * @param string|null $additional_code Additional HTML code to append to the input element.
 * @return string The generated HTML input element with type "email".
 */
function form_email(string $name, ?string $value = null, ?array $attributes = null, ?string $additional_code = null): string {
    $attributes = $attributes ?? [];
    $attributes['type'] = 'email';
    return form_input($name, $value, $attributes, $additional_code);
}

/**
 * Generate an HTML hidden input field.
 *
 * @param string $name The name attribute for the hidden input field.
 * @param string|null $value The initial value of the hidden input field. If not provided, it will be empty.
 * @param string|null $additional_code Additional HTML code to append to the hidden input field.
 * @return string The generated HTML hidden input field.
 */
function form_hidden(string $name, $value = null, ?string $additional_code = null): string {
    $attributes = ['type' => 'hidden'];
    return form_input($name, $value, $attributes, $additional_code);
}

/**
 * Generate an HTML textarea element.
 *
 * @param string $name The name attribute for the textarea element.
 * @param string|null $value The initial value of the textarea. If not provided, it will be empty.
 * @param array|null $attributes An associative array of HTML attributes for the textarea.
 * @param string|null $additional_code Additional HTML code to append to the textarea element.
 * @return string The generated HTML textarea element.
 */
function form_textarea(string $name, ?string $value = null, ?array $attributes = null, ?string $additional_code = null): string {
    $attributes = $attributes ?? [];
    $attributes['name'] = $name;
    
    $html = '<textarea' . get_attributes_str($attributes);
    
    if ($additional_code !== null) {
        $html .= ' ' . $additional_code;
    }
    
    $html .= '>' . htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8') . '</textarea>';
    
    return $html;
}

/**
 * Generate an HTML submit button element.
 *
 * @param string $name The name attribute for the button element.
 * @param string|null $value The value of the button. If not provided, the button's name will be used as the value.
 * @param array|null $attributes An associative array of HTML attributes for the button.
 * @param string|null $additional_code Additional HTML code to append to the button element.
 * @return string The generated HTML submit button element.
 */
function form_submit(string $name, ?string $value = null, ?array $attributes = null, ?string $additional_code = null): string {
    $attributes = $attributes ?? [];
    $attributes['type'] = 'submit';
    $attributes['name'] = $name;
    $value = $value ?? $name;
    $attributes['value'] = $value;
    
    $html = '<button' . get_attributes_str($attributes);
    
    if ($additional_code !== null) {
        $html .= ' ' . $additional_code;
    }
    
    $html .= '>' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</button>';
    
    return $html;
}

/**
 * Generate an HTML button element.
 *
 * @param string $name The name attribute for the button element.
 * @param string|null $value The value of the button.
 * @param array|null $attributes An associative array of HTML attributes for the button.
 * @param string|null $additional_code Additional HTML code to append to the button element.
 * @return string The generated HTML button element.
 */
function form_button(string $name, ?string $value = null, ?array $attributes = null, ?string $additional_code = null): string {
    $attributes = $attributes ?? [];
    $attributes['type'] = 'button';
    return form_submit($name, $value, $attributes, $additional_code);
}

/**
 * Generate an HTML input element with type "radio".
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value of the radio button.
 * @param bool $checked Whether the radio button should be checked (true) or unchecked (false).
 * @param array|null $attributes An associative array of HTML attributes for the input.
 * @param string|null $additional_code Additional HTML code to append to the input element.
 * @return string The generated HTML input element with type "radio".
 */
function form_radio(string $name, ?string $value = null, bool $checked = false, ?array $attributes = null, ?string $additional_code = null): string {
    $attributes = $attributes ?? [];
    $attributes['type'] = 'radio';
    $attributes['name'] = $name;
    $attributes['value'] = $value ?? '1';
    
    if ($checked) {
        $attributes['checked'] = 'checked';
    }

    $html = '<input' . get_attributes_str($attributes);
    
    if ($additional_code !== null) {
        $html .= ' ' . $additional_code;
    }

    return $html . '>';
}

/**
 * Generate an HTML input element with type "checkbox".
 *
 * @param string $name The name attribute for the input element.
 * @param string|null $value The value of the checkbox when checked.
 * @param bool $checked Whether the checkbox should be checked (true) or unchecked (false).
 * @param array|null $attributes An associative array of HTML attributes for the input.
 * @param string|null $additional_code Additional HTML code to append to the input element.
 * @return string The generated HTML input element with type "checkbox".
 */
function form_checkbox(string $name, ?string $value = null, bool $checked = false, ?array $attributes = null, ?string $additional_code = null): string {
    $attributes = $attributes ?? [];
    $attributes['type'] = 'checkbox';
    return form_radio($name, $value, $checked, $attributes, $additional_code);
}

/**
 * Generate an HTML select menu.
 *
 * @param string $name The name attribute for the select element.
 * @param array $options An associative array of options (value => text).
 * @param string|null $selected_key The key of the selected option, if any.
 * @param array|null $attributes An array of HTML attributes for the select element.
 * @param string|null $additional_code Additional HTML code to include in the select element.
 * @return string The generated HTML for the select menu.
 */
function form_dropdown(string $name, array $options, ?string $selected_key = null, ?array $attributes = null, ?string $additional_code = null): string {
    $attributes = $attributes ?? [];
    $attributes['name'] = $name;
    
    $html = '<select' . get_attributes_str($attributes);
    
    if ($additional_code !== null) {
        $html .= ' ' . $additional_code;
    }
    
    $html .= ">\n";

    foreach ($options as $option_key => $option_value) {
        $option_attributes = ['value' => $option_key];
        if ($option_key === $selected_key) {
            $option_attributes['selected'] = 'selected';
        }
        $html .= '    <option' . get_attributes_str($option_attributes) . '>' 
               . htmlspecialchars($option_value, ENT_QUOTES, 'UTF-8') . "</option>\n";
    }

    $html .= '</select>';
    return $html;
}

/**
 * Generate an HTML file input element.
 *
 * @param string $name The name attribute for the file input.
 * @param array|null $attributes An array of HTML attributes for the file input.
 * @param string|null $additional_code Additional HTML code to include in the file input.
 * @return string The generated HTML for the file input element.
 */
function form_file_select(string $name, ?array $attributes = null, ?string $additional_code = null): string {
    $attributes = $attributes ?? [];
    $attributes['type'] = 'file';
    return form_input($name, null, $attributes, $additional_code);
}

/**
 * Retrieve and clean a value from the POST data.
 *
 * @param string $field_name The name of the POST field to retrieve.
 * @param bool $clean_up Whether to clean up the retrieved value (default is false).
 * @return string|int|float|null The value retrieved from the POST data, or null if not found.
 */
function post(string $field_name, bool $clean_up = false): string|int|float|null {
    $value = $_POST[$field_name] ?? null;

    if ($value === null) {
        return null;
    }

    if ($clean_up) {
        $value = trim($value);
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        if (is_numeric($value)) {
            return filter_var($value, FILTER_VALIDATE_INT) !== false
                ? (int) $value
                : (float) $value;
        }
    }

    return $value;
}

/**
 * Generates and returns validation error messages in HTML or JSON format.
 *
 * @param string|int|null $first_arg Optional HTML to open each error message, HTTP status code for JSON output, or null.
 * @param string|null $closing_html Optional HTML to close each error message.
 * @return string|null Returns a string of formatted validation errors or null if no errors are present.
 */
function validation_errors(string|int|null $first_arg = null, ?string $closing_html = null): ?string {
    if (!isset($_SESSION['form_submission_errors'])) {
        return null;
    }

    $form_submission_errors = $_SESSION['form_submission_errors'];

    if (is_int($first_arg) && $first_arg >= 400 && $first_arg <= 499) {
        return json_validation_errors($form_submission_errors, $first_arg);
    }

    if (isset($first_arg) && !isset($closing_html)) {
        return inline_validation_errors($form_submission_errors, $first_arg);
    }

    return general_validation_errors($form_submission_errors, $first_arg, $closing_html);
}

/**
 * Generates JSON-formatted validation errors and sends an HTTP response.
 *
 * @param array $errors The validation errors.
 * @param int $status_code The HTTP status code to send.
 * @return never
 */
function json_validation_errors(array $errors, int $status_code): never {
    $json_errors = array_map(
        fn($field, $messages) => ['field' => $field, 'messages' => $messages],
        array_keys($errors),
        $errors
    );

    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($json_errors, JSON_THROW_ON_ERROR);
    unset($_SESSION['form_submission_errors']);
    exit();
}

/**
 * Generates inline validation errors for a specific field.
 *
 * @param array $errors The validation errors.
 * @param string $field The field to display errors for.
 * @return string The formatted inline validation errors.
 */
function inline_validation_errors(array $errors, string $field): string {
    if (!isset($errors[$field])) {
        return '';
    }

    $validation_err_str = '<div class="validation-error-report">';
    foreach ($errors[$field] as $validation_error) {
        $validation_err_str .= '<div>&#9679; ' . htmlspecialchars($validation_error, ENT_QUOTES, 'UTF-8') . '</div>';
    }
    $validation_err_str .= '</div>';

    return $validation_err_str;
}

/**
 * Generates general validation errors.
 *
 * @param array $errors The validation errors.
 * @param string|null $opening_html HTML to open each error message.
 * @param string|null $closing_html HTML to close each error message.
 * @return string The formatted general validation errors.
 */
function general_validation_errors(array $errors, ?string $opening_html = null, ?string $closing_html = null): string {
    if (!isset($opening_html, $closing_html)) {
        $opening_html = defined('ERROR_OPEN') ? ERROR_OPEN : '<p style="color: red;">';
        $closing_html = defined('ERROR_CLOSE') ? ERROR_CLOSE : '</p>';
    }

    $validation_err_str = '';
    foreach ($errors as $field_errors) {
        foreach ($field_errors as $error) {
            $validation_err_str .= $opening_html . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . $closing_html;
        }
    }

    unset($_SESSION['form_submission_errors']);
    return $validation_err_str;
}