<?php
/**
 * Plugin Name: Patient I/O Tracker
 * Plugin URI: https://example.com/patient-io-tracker
 * Description: 환자의 섭취량(Intake)과 배설량(Output)을 관리하는 플러그인
 * Version: 1.0.0
 * Author: Healthcare Developer
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: patient-io-tracker
 * Domain Path: /languages
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

// 플러그인 상수 정의
define('PIO_PLUGIN_VERSION', '1.0.0');
define('PIO_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PIO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PIO_TABLE_NAME', 'patient_io_records');

// 필요한 클래스 파일 로드
require_once PIO_PLUGIN_PATH . 'includes/class-database.php';
require_once PIO_PLUGIN_PATH . 'includes/class-api.php';
require_once PIO_PLUGIN_PATH . 'includes/class-shortcode.php';

/**
 * 플러그인 메인 클래스
 */
class Patient_IO_Tracker {

    private static $instance = null;
    private $database;
    private $api;
    private $shortcode;

    /**
     * 싱글톤 인스턴스 반환
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 생성자
     */
    private function __construct() {
        $this->init_hooks();
        $this->init_classes();
    }

    /**
     * 훅 초기화
     */
    private function init_hooks() {
        // 플러그인 활성화/비활성화 훅
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // 스크립트 및 스타일 로드
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // REST API 초기화
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * 클래스 초기화
     */
    private function init_classes() {
        $this->database = new PIO_Database();
        $this->api = new PIO_API();
        $this->shortcode = new PIO_Shortcode();
    }

    /**
     * 플러그인 활성화
     */
    public function activate() {
        $this->database = new PIO_Database();
        $this->database->create_table();
        flush_rewrite_rules();
    }

    /**
     * 플러그인 비활성화
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * 스크립트 및 스타일 로드
     */
    public function enqueue_assets() {
        // CSS
        wp_enqueue_style(
            'patient-io-tracker-style',
            PIO_PLUGIN_URL . 'assets/css/style.css',
            array(),
            PIO_PLUGIN_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'patient-io-tracker-app',
            PIO_PLUGIN_URL . 'assets/js/app.js',
            array('jquery'),
            PIO_PLUGIN_VERSION,
            true
        );

        // JavaScript에 데이터 전달
        wp_localize_script('patient-io-tracker-app', 'pioData', array(
            'restUrl' => rest_url('patient-io/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'categories' => array(
                'intake' => array(
                    'water' => array('label' => '물', 'icon' => '💧', 'unit' => 'ml'),
                    'meal' => array('label' => '식사', 'icon' => '🍚', 'unit' => 'g'),
                    'medicine' => array('label' => '약', 'icon' => '💊', 'unit' => '정'),
                    'beverage' => array('label' => '음료', 'icon' => '🥤', 'unit' => 'ml'),
                ),
                'output' => array(
                    'urine' => array('label' => '소변', 'icon' => '🚽', 'unit' => 'ml'),
                    'stool' => array('label' => '대변', 'icon' => '💩', 'unit' => '회'),
                    'diarrhea' => array('label' => '설사', 'icon' => '💨', 'unit' => '회'),
                ),
            ),
        ));
    }

    /**
     * REST API 라우트 등록
     */
    public function register_rest_routes() {
        $this->api->register_routes();
    }
}

// 플러그인 초기화
function patient_io_tracker_init() {
    return Patient_IO_Tracker::get_instance();
}
add_action('plugins_loaded', 'patient_io_tracker_init');
