<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 컨테이너 인스턴스를 반환한다.
 * @return \Pimple\Container|null
 */
function i_love_fran(): \Pimple\Container
{
    static $fran = null;

    if ($fran === null) {
        // .env 파일 로드
        (new CodeIgniter\Config\DotEnv(__DIR__ . '/..'))->load();

        if (get_env_value('fran_environment') === 'development') {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }

        // Pimple 컨테이너 인스턴스 생성
        $fran = new \Pimple\Container([
            'qb' => function () {
                return new \CI_Qb(); // 쿼리 빌더
            },
            'tpl' => function () {
                return new \Template_\Template_(); // Template
            },
            'rb' => function () {
                return new \R(); // RedBeanPHP
            },
            'formValidation' => function () {
                return new \CodeIgniter\Lib\FormValidation(); // 폼검증
            },
        ]);
    }

    return $fran;
}

/**
 * 컨테이너에 등록된 서비스를 반환한다.
 * @param $key
 * @return mixed|\Pimple\Container|null
 */
function get_fran($key = false)
{
    if ($key) {
        return i_love_fran()->offsetGet($key);
    }

    return fran();
}

/**
 * 컨테이너에 서비스를 등록한다.
 * @param $key
 * @param $value
 * @return void
 */
function set_fran($key, $value)
{
    i_love_fran()[$key] = $value;
}

/**
 * 컨테이너에 등록된 쿼리빌더를 반환한다.
 * @param $key
 * @return void
 */
function qb($database = false): \Hoksi\Qb\Qb
{
    static $qb = [];
    if (class_exists('\Swoole\Coroutine')) {
        $cid = \Swoole\Coroutine::getCid();
    } else {
        $cid = 'default';
    }

    if (!isset($qb[$cid])) {
        $qb[$cid] = new \Hoksi\Qb\Qb($database);
    }

    echo "cid : {$cid}\n";

    return $qb[$cid];
}

function fb_import($resource, $params = false, $opt = false)
{
    $res_parse = explode('.', $resource);

    if (!empty($res_parse) && count($res_parse) >= 2) {
        $res_type = array_shift($res_parse);

        return getResource($res_type, $res_parse, $params, $opt);
    } else {
        show_error('Resource is Empty!');
    }
}

function getResource($type, $res_params, $params, $opt)
{
    switch ($type) {
        case 'model':
            return getObj($res_params, $type, $params);
    }

    return false;
}

function getObj($class, $postfix, $params = null)
{
    $class = array_map(function ($item) {
        return ucfirst($item);
    }, array_merge(['Forbiz', $postfix], $class));

    if (!empty($class)) {
        $coreClass = implode('\\', $class);

        if (class_exists($coreClass)) {
            return new $coreClass($params);
        }
    }

    return false;
}

function is_cli()
{
    return (PHP_SAPI === 'cli' or defined('STDIN'));
}

function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered')
{
    $status_code = abs($status_code);
    if ($status_code < 100) {
        $exit_status = $status_code + 9; // 9 is EXIT__AUTO_MIN
        $status_code = 500;
    } else {
        $exit_status = 1; // EXIT_ERROR
    }

    if (is_cli()) {
        $message = "\t" . (is_array($message) ? implode("\n\t", $message) : $message);
    } else {
        set_status_header($status_code);
        $message = '<p>' . (is_array($message) ? nl2br(implode('</p><p>', $message)) : nl2br($message)) . '</p>';
    }

    throw new \Exception($message);
}

/**
 * 로그를 작성한다.
 * @param $level
 * @param $msg
 * @return bool
 */
function log_message($level, $msg)
{
    static $filepath = false;

    if (!defined('THRESHOLD_LOG_LEVEL')) {
        define('THRESHOLD_LOG_LEVEL', intval(get_env_value('logger_threshold')));
        define('FRAN_LOG_FILE_PATH', realpath(BASEPATH . '/../' . get_env_value('logger_path')));
    }

    if (FRAN_LOG_FILE_PATH !== false) {
        $filepath = FRAN_LOG_FILE_PATH . (DIRECTORY_SEPARATOR . 'log-' . date('Y-m-d') . '.php');
    }

    $_levels = ['ERROR' => 1, 'DEBUG' => 2, 'INFO' => 3, 'ALL' => 4];

    $level = strtoupper($level);

    if ($filepath === false || !isset($_levels[$level]) || ($_levels[$level] > THRESHOLD_LOG_LEVEL)) {
        return FALSE;
    }

    $message = '';

    if (!file_exists($filepath)) {
        $newfile = TRUE;
        $message .= "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\n\n";
    }

    if (!$fp = @fopen($filepath, 'ab')) {
        return FALSE;
    }

    flock($fp, LOCK_EX);

    $date = date('Y-m-d H:i:s');
    $message .= $level . ' - ' . $date . ' --> ' . $msg . PHP_EOL;

    for ($written = 0, $length = mb_strlen($message, '8bit'); $written < $length; $written += $result) {
        if (($result = fwrite($fp, mb_substr($message, $written, null, '8bit'))) === FALSE) {
            break;
        }
    }

    flock($fp, LOCK_UN);
    fclose($fp);

    if (isset($newfile) && $newfile === TRUE) {
        chmod($filepath, 0644);
    }

    return is_int($result);
}

/**
 * Set HTTP Status Header
 *
 * @param int    the status code
 * @param string
 * @return    void
 */
function set_status_header($code = 200, $text = '')
{
    if (is_cli()) {
        return;
    }

    if (empty($code) or !is_numeric($code)) {
        show_error('Status codes must be numeric', 500);
    }

    if (empty($text)) {
        is_int($code) or $code = (int)$code;
        $stati = array(
            100 => 'Continue',
            101 => 'Switching Protocols',

            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',

            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',

            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            422 => 'Unprocessable Entity',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',

            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            511 => 'Network Authentication Required',
        );

        if (isset($stati[$code])) {
            $text = $stati[$code];
        } else {
            show_error('No status text available. Please check your status code number or supply your own message text.', 500);
        }
    }

    if (strpos(PHP_SAPI, 'cgi') === 0) {
        header('Status: ' . $code . ' ' . $text, TRUE);
        return;
    }

    $server_protocol = (isset($_SERVER['SERVER_PROTOCOL']) && in_array($_SERVER['SERVER_PROTOCOL'], array('HTTP/1.0', 'HTTP/1.1', 'HTTP/2'), TRUE))
        ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
    header($server_protocol . ' ' . $code . ' ' . $text, TRUE, $code);
}

/**
 * Remove Invisible Characters
 *
 * This prevents sandwiching null characters
 * between ascii characters, like Java\0script.
 *
 * @param string
 * @param bool
 * @return    string
 */
function remove_invisible_characters($str, $url_encoded = TRUE)
{
    $non_displayables = array();

    // every control character except newline (dec 10),
    // carriage return (dec 13) and horizontal tab (dec 09)
    if ($url_encoded) {
        $non_displayables[] = '/%0[0-8bcef]/i';    // url encoded 00-08, 11, 12, 14, 15
        $non_displayables[] = '/%1[0-9a-f]/i';    // url encoded 16-31
        $non_displayables[] = '/%7f/i';    // url encoded 127
    }

    $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';    // 00-08, 11, 12, 14-31, 127

    do {
        $str = preg_replace($non_displayables, '', $str, -1, $count);
    } while ($count);

    return $str;
}

if (!function_exists('sess_val')) {
    /**
     * 세션에서 데이터 조회
     * @param string ...$keys
     * @return array|mixed|string
     */
    function sess_val(string ...$keys)
    {
        $len = count($keys);
        if ($len > 0) {
            switch ($len) {
                case 4:
                    return $_SESSION[$keys[0]][$keys[1]][$keys[2]][$keys[3]] ?? '';
                case 3:
                    return $_SESSION[$keys[0]][$keys[1]][$keys[2]] ?? '';
                case 2:
                    return $_SESSION[$keys[0]][$keys[1]] ?? '';
                case 1:
                    return $_SESSION[$keys[0]] ?? '';
            }

            return '';
        } else {
            return (isset($_SESSION) ? $_SESSION : []);
        }
    }
}

if (!function_exists('cook_val')) {
    /**
     * 쿠키에서 데이터 조회
     * @param string ...$keys
     * @return array|mixed|string
     */
    function cook_val(string ...$keys)
    {
        $len = count($keys);
        if ($len > 0) {

            switch ($len) {
                case 4:
                    return $_COOKIE[$keys[0]][$keys[1]][$keys[2]][$keys[3]] ?? '';
                case 3:
                    return $_COOKIE[$keys[0]][$keys[1]][$keys[2]] ?? '';
                case 2:
                    return $_COOKIE[$keys[0]][$keys[1]] ?? '';
                case 1:
                    return $_COOKIE[$keys[0]] ?? '';
            }

            return '';
        } else {
            return (isset($_COOKIE) ? $_COOKIE : []);
        }
    }
}

if (!function_exists('g_val')) {
    /**
     * 전역변수에서 데이터 조회
     * @param ...$keys
     * @return mixed|string
     */
    function g_val(...$keys)
    {
        switch (count($keys)) {
            case 1:
                return $GLOBALS[$keys[0]] ?? '';
            case 2:
                return $GLOBALS[$keys[0]][$keys[1]] ?? '';
            case 3:
                return $GLOBALS[$keys[0]][$keys[1]][$keys[2]] ?? '';
            case 4:
                return $GLOBALS[$keys[0]][$keys[1]][$keys[2]][$keys[3]] ?? '';
        }

        return '';
    }
}

if (!function_exists('get_val')) {
    /**
     * $_GET에서 데이터 조회
     * @param ...$keys
     * @return mixed|string
     */
    function get_val($key = null, $defualt = null, $escape = false)
    {
        if ($key !== null) {
            if (isset($_GET[$key])) {
                return $escape ? fb_esc($_GET[$key]) : $_GET[$key];
            } else {
                return $defualt;
            }
        } else {
            return $_GET;
        }
    }
}

if (!function_exists('post_val')) {
    /**
     * $_POST에서 데이터 조회
     * @param ...$keys
     * @return mixed|string
     */
    function post_val($key = null, $defualt = '', $escape = false)
    {
        if ($key !== null) {
            if (isset($_POST[$key])) {
                return $escape ? fb_esc($_POST[$key]) : $_POST[$key];
            } else {
                return $defualt;
            }
        } else {
            return $_POST;
        }
    }
}

if (!function_exists('redirect')) {
    /**
     * Header Redirect
     *
     * Header redirect in two flavors
     * For very fine grained control over headers, you could use the Output
     * Library's set_header() function.
     *
     * @param string $uri URL
     * @param string $method Redirect method
     *            'auto', 'location' or 'refresh'
     * @param int $code HTTP Response status code
     * @return    void
     */
    function redirect($uri = '', $method = 'auto', $code = NULL)
    {
        // IIS environment likely? Use 'refresh' for better compatibility
        if ($method === 'auto' && isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== FALSE) {
            $method = 'refresh';
        } elseif ($method !== 'refresh' && (empty($code) or !is_numeric($code))) {
            if (isset($_SERVER['SERVER_PROTOCOL'], $_SERVER['REQUEST_METHOD']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1') {
                $code = ($_SERVER['REQUEST_METHOD'] !== 'GET')
                    ? 303    // reference: http://en.wikipedia.org/wiki/Post/Redirect/Get
                    : 307;
            } else {
                $code = 302;
            }
        }

        switch ($method) {
            case 'refresh':
                header('Refresh:0;url=' . $uri);
                break;
            default:
                header('Location: ' . $uri, TRUE, $code);
                break;
        }
        exit;
    }
}

if (!function_exists('fb_esc')) {
    /**
     * Performs simple auto-escaping of data for security reasons.
     * Might consider making this more complex at a later date.
     *
     * If $data is a string, then it simply escapes and returns it.
     * If $data is an array, then it loops over it, escaping each
     * 'value' of the key/value pairs.
     *
     * @param array|string $data
     * @phpstan-param 'html'|'js'|'css'|'url'|'attr'|'raw' $context
     * @param string|null $encoding Current encoding for escaping.
     *                              If not UTF-8, we convert strings from this encoding
     *                              pre-escaping and back to this encoding post-escaping.
     *
     * @return array|string
     *
     * @throws InvalidArgumentException
     */
    function fb_esc($data, string $context = 'html', ?string $encoding = null)
    {
        if (is_array($data)) {
            foreach ($data as &$value) {
                $value = fb_esc($value, $context);
            }
        }

        if (is_string($data)) {
            $context = strtolower($context);

            // Provide a way to NOT escape data since
            // this could be called automatically by
            // the View library.
            if ($context === 'raw') {
                return $data;
            }

            if (!in_array($context, ['html', 'js', 'css', 'url', 'attr'], true)) {
                throw new InvalidArgumentException('Invalid escape context provided.');
            }

            $method = $context === 'attr' ? 'escapeHtmlAttr' : 'escape' . ucfirst($context);

            static $escaper;
            if (!$escaper) {
                $escaper = new \Laminas\Escaper\Escaper($encoding);
            }

            if ($encoding && $escaper->getEncoding() !== $encoding) {
                $escaper = new \Laminas\Escaper\Escaper($encoding);
            }

            $data = $escaper->{$method}($data);
        }

        return $data;
    }
}

if (!function_exists('fb_now')) {
    function fb_now()
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('force_download')) {
    /**
     * Force Download
     *
     * Generates headers that force a download to happen
     *
     * @param string    filename
     * @param mixed    the data to be downloaded
     * @return    void
     */
    function force_download($filename = '', $data = '')
    {
        if ($filename === '' or $data === '') {
            return;
        } elseif ($data === NULL) {
            if (!@is_file($filename) or ($filesize = @filesize($filename)) === FALSE) {
                return;
            }

            $filepath = $filename;
            $filename = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $filename));
            $filename = end($filename);
        } else {
            $filesize = strlen($data);
        }

        // Set the default MIME type to send
        $mime = 'application/octet-stream';

        $x = explode('.', $filename);
        $extension = end($x);

        /* It was reported that browsers on Android 2.1 (and possibly older as well)
         * need to have the filename extension upper-cased in order to be able to
         * download it.
         *
         * Reference: http://digiblog.de/2011/04/19/android-and-the-download-file-headers/
         */
        if (count($x) !== 1 && isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Android\s(1|2\.[01])/', $_SERVER['HTTP_USER_AGENT'])) {
            $x[count($x) - 1] = strtoupper($extension);
            $filename = implode('.', $x);
        }

        if ($data === NULL && ($fp = @fopen($filepath, 'rb')) === FALSE) {
            return;
        }

        // Clean output buffer
        if (ob_get_level() !== 0 && @ob_end_clean() === FALSE) {
            @ob_clean();
        }

        // Generate the server headers
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $filesize);
        header('Cache-Control: private, no-transform, no-store, must-revalidate');

        // If we have raw data - just dump it
        if ($data !== NULL) {
            exit($data);
        }

        // Flush 1MB chunks of data
        while (!feof($fp) && ($data = fread($fp, 1048576)) !== FALSE) {
            echo $data;
        }

        fclose($fp);
        exit;
    }
}

if (!function_exists('get_env_value')) {
    function get_env_value(string $property)
    {
        switch (true) {
            case array_key_exists($property, $_ENV):
                return $_ENV[$property];

            case array_key_exists($property, $_SERVER):
                return $_SERVER[$property];

            default:
                $value = getenv($property);

                return $value === false ? null : $value;
        }
    }
}

if (!function_exists('fb_valid_date')) {
    function fb_valid_date($dateStr, $format = 'Y-m-d')
    {
        return date($format, strtotime($dateStr));
    }
}

if (!function_exists('xss_clean')) {
    function xss_clean($data, string $context = 'html', ?string $encoding = null)
    {
        return fb_esc($data, $context, $encoding);
    }
}

if (!function_exists('tpl')) {
    function tpl(): \Template_\Template_
    {
        return fran()['tpl'];;
    }
}

if (!function_exists('generate_emcrypt_key')) {
    function generate_emcrypt_key(): string
    {
        return bin2hex(random_bytes(64));
    }
}

if (!function_exists('form_validation')) {

    /**
     * 필수 데이타 점검
     * @param array $formFieldList
     * @param array $data
     * @return bool
     */
    function form_validation($formFieldList, $data = []): bool
    {
        if (is_array($formFieldList) && !empty($formFieldList)) {
            /* @var $validater FormValidation */
            $validater = get_fran('formValidation');

            $validater->reset_validation();

            if (empty($data) && empty($_POST)) {
                return false;
            }

            if (!empty($data)) {
                $validater->set_data($data);
            } else {
                $validater->set_data($_POST);
            }

            foreach ($formFieldList as $field) {
                $validater->set_rules($field, ucfirst($field), 'required', [
                    'required' => 'The {field} field is required.',
                ]);
            }

            return $validater->run();
        }

        return true;
    }
}

if (!function_exists('validation_errors')) {
    /**
     * Validation Error String
     *
     * Returns all the errors associated with a form submission. This is a helper
     * function for the form validation class.
     *
     * @param string
     * @param string
     * @return    string
     */
    function validation_errors($prefix = '', $suffix = '')
    {
        return get_fran('formValidation')->error_string($prefix, $suffix);
    }
}

if (!function_exists('is_php')) {
    /**
     * Determines if the current version of PHP is equal to or greater than the supplied value
     *
     * @param string
     * @return    bool    TRUE if the current version is $version or higher
     */
    function is_php($version)
    {
        static $_is_php;
        $version = (string)$version;

        if (!isset($_is_php[$version])) {
            $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
        }

        return $_is_php[$version];
    }
}

function rb(): \RedBeanPHP\Facade
{
    if (get_env_value('redbean_use') !== 'true') {
        show_error('RedBean is not enabled!');
    }

    if (get_fran('rb')::getPDO() === null) {
        get_fran('rb')::setup(get_env_value('redbean_default_dsn'), get_env_value('readbean_username'), get_env_value('redbean_password'));
    }

    return get_fran('rb');
}