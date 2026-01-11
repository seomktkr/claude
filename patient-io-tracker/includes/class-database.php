<?php
/**
 * 데이터베이스 관리 클래스
 */

if (!defined('ABSPATH')) {
    exit;
}

class PIO_Database {

    private $table_name;

    /**
     * 생성자
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . PIO_TABLE_NAME;
    }

    /**
     * 테이블 이름 반환
     */
    public function get_table_name() {
        return $this->table_name;
    }

    /**
     * 테이블 생성
     */
    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            patient_id bigint(20) unsigned DEFAULT 0,
            record_type varchar(20) NOT NULL,
            category varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL DEFAULT 0,
            unit varchar(20) NOT NULL DEFAULT 'ml',
            memo text,
            recorded_at datetime NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY patient_id (patient_id),
            KEY record_type (record_type),
            KEY category (category),
            KEY recorded_at (recorded_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * 기록 추가
     */
    public function insert_record($data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'patient_id' => isset($data['patient_id']) ? intval($data['patient_id']) : 0,
                'record_type' => sanitize_text_field($data['record_type']),
                'category' => sanitize_text_field($data['category']),
                'amount' => floatval($data['amount']),
                'unit' => sanitize_text_field($data['unit']),
                'memo' => isset($data['memo']) ? sanitize_textarea_field($data['memo']) : '',
                'recorded_at' => sanitize_text_field($data['recorded_at']),
            ),
            array('%d', '%s', '%s', '%f', '%s', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_insert_error', 'Failed to insert record');
        }

        return $wpdb->insert_id;
    }

    /**
     * 기록 수정
     */
    public function update_record($id, $data) {
        global $wpdb;

        $update_data = array();
        $format = array();

        if (isset($data['amount'])) {
            $update_data['amount'] = floatval($data['amount']);
            $format[] = '%f';
        }

        if (isset($data['unit'])) {
            $update_data['unit'] = sanitize_text_field($data['unit']);
            $format[] = '%s';
        }

        if (isset($data['memo'])) {
            $update_data['memo'] = sanitize_textarea_field($data['memo']);
            $format[] = '%s';
        }

        if (isset($data['recorded_at'])) {
            $update_data['recorded_at'] = sanitize_text_field($data['recorded_at']);
            $format[] = '%s';
        }

        if (empty($update_data)) {
            return new WP_Error('no_data', 'No data to update');
        }

        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => intval($id)),
            $format,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_update_error', 'Failed to update record');
        }

        return true;
    }

    /**
     * 기록 삭제
     */
    public function delete_record($id) {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            array('id' => intval($id)),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_delete_error', 'Failed to delete record');
        }

        return true;
    }

    /**
     * 단일 기록 조회
     */
    public function get_record($id) {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            intval($id)
        );

        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * 날짜별 기록 조회
     */
    public function get_records_by_date($date, $patient_id = 0) {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE DATE(recorded_at) = %s
            AND patient_id = %d
            ORDER BY recorded_at ASC",
            $date,
            intval($patient_id)
        );

        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * 날짜 범위별 기록 조회
     */
    public function get_records_by_date_range($start_date, $end_date, $patient_id = 0) {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE DATE(recorded_at) BETWEEN %s AND %s
            AND patient_id = %d
            ORDER BY recorded_at ASC",
            $start_date,
            $end_date,
            intval($patient_id)
        );

        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * 일일 요약 조회
     */
    public function get_daily_summary($date, $patient_id = 0) {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT
                record_type,
                category,
                unit,
                SUM(amount) as total_amount,
                COUNT(*) as count
            FROM {$this->table_name}
            WHERE DATE(recorded_at) = %s
            AND patient_id = %d
            GROUP BY record_type, category, unit
            ORDER BY record_type, category",
            $date,
            intval($patient_id)
        );

        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * 사용 가능한 날짜 목록 조회
     */
    public function get_available_dates($patient_id = 0, $limit = 30) {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT DISTINCT DATE(recorded_at) as record_date
            FROM {$this->table_name}
            WHERE patient_id = %d
            ORDER BY record_date DESC
            LIMIT %d",
            intval($patient_id),
            intval($limit)
        );

        return $wpdb->get_col($sql);
    }
}
