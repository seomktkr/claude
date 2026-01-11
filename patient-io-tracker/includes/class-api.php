<?php
/**
 * REST API 클래스
 */

if (!defined('ABSPATH')) {
    exit;
}

class PIO_API {

    private $namespace = 'patient-io/v1';
    private $database;

    /**
     * 생성자
     */
    public function __construct() {
        $this->database = new PIO_Database();
    }

    /**
     * REST API 라우트 등록
     */
    public function register_routes() {
        // 기록 생성
        register_rest_route($this->namespace, '/records', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_record'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => $this->get_record_args(),
        ));

        // 기록 조회 (날짜별)
        register_rest_route($this->namespace, '/records', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_records'),
            'permission_callback' => '__return_true',
            'args' => array(
                'date' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Date in Y-m-d format',
                ),
                'patient_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 0,
                ),
            ),
        ));

        // 단일 기록 조회
        register_rest_route($this->namespace, '/records/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_record'),
            'permission_callback' => '__return_true',
        ));

        // 기록 수정
        register_rest_route($this->namespace, '/records/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array($this, 'update_record'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        // 기록 삭제
        register_rest_route($this->namespace, '/records/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => array($this, 'delete_record'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        // 일일 요약
        register_rest_route($this->namespace, '/summary', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_summary'),
            'permission_callback' => '__return_true',
            'args' => array(
                'date' => array(
                    'required' => false,
                    'type' => 'string',
                ),
                'patient_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 0,
                ),
            ),
        ));

        // 사용 가능한 날짜 목록
        register_rest_route($this->namespace, '/dates', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_dates'),
            'permission_callback' => '__return_true',
            'args' => array(
                'patient_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 0,
                ),
            ),
        ));
    }

    /**
     * 권한 확인
     */
    public function check_permission($request) {
        // 기본적으로 nonce 확인
        $nonce = $request->get_header('X-WP-Nonce');
        if ($nonce && wp_verify_nonce($nonce, 'wp_rest')) {
            return true;
        }
        return true; // 개발 단계에서는 허용
    }

    /**
     * 기록 생성 인자 정의
     */
    private function get_record_args() {
        return array(
            'record_type' => array(
                'required' => true,
                'type' => 'string',
                'enum' => array('intake', 'output'),
                'description' => 'Type of record: intake or output',
            ),
            'category' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Category of record',
            ),
            'amount' => array(
                'required' => true,
                'type' => 'number',
                'description' => 'Amount',
            ),
            'unit' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Unit of measurement',
            ),
            'memo' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Optional memo',
            ),
            'recorded_at' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Recording datetime',
            ),
            'patient_id' => array(
                'required' => false,
                'type' => 'integer',
                'default' => 0,
            ),
        );
    }

    /**
     * 기록 생성
     */
    public function create_record($request) {
        $data = array(
            'patient_id' => $request->get_param('patient_id') ?? 0,
            'record_type' => $request->get_param('record_type'),
            'category' => $request->get_param('category'),
            'amount' => $request->get_param('amount'),
            'unit' => $request->get_param('unit'),
            'memo' => $request->get_param('memo') ?? '',
            'recorded_at' => $request->get_param('recorded_at') ?? current_time('mysql'),
        );

        $result = $this->database->insert_record($data);

        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message(),
            ), 500);
        }

        $record = $this->database->get_record($result);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $record,
        ), 201);
    }

    /**
     * 기록 목록 조회
     */
    public function get_records($request) {
        $date = $request->get_param('date') ?? date('Y-m-d');
        $patient_id = $request->get_param('patient_id') ?? 0;

        $records = $this->database->get_records_by_date($date, $patient_id);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $records,
            'date' => $date,
        ), 200);
    }

    /**
     * 단일 기록 조회
     */
    public function get_record($request) {
        $id = $request->get_param('id');
        $record = $this->database->get_record($id);

        if (!$record) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Record not found',
            ), 404);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $record,
        ), 200);
    }

    /**
     * 기록 수정
     */
    public function update_record($request) {
        $id = $request->get_param('id');
        $data = array();

        if ($request->get_param('amount') !== null) {
            $data['amount'] = $request->get_param('amount');
        }
        if ($request->get_param('unit') !== null) {
            $data['unit'] = $request->get_param('unit');
        }
        if ($request->get_param('memo') !== null) {
            $data['memo'] = $request->get_param('memo');
        }
        if ($request->get_param('recorded_at') !== null) {
            $data['recorded_at'] = $request->get_param('recorded_at');
        }

        $result = $this->database->update_record($id, $data);

        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message(),
            ), 500);
        }

        $record = $this->database->get_record($id);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $record,
        ), 200);
    }

    /**
     * 기록 삭제
     */
    public function delete_record($request) {
        $id = $request->get_param('id');

        $result = $this->database->delete_record($id);

        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message(),
            ), 500);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Record deleted',
        ), 200);
    }

    /**
     * 일일 요약 조회
     */
    public function get_summary($request) {
        $date = $request->get_param('date') ?? date('Y-m-d');
        $patient_id = $request->get_param('patient_id') ?? 0;

        $summary = $this->database->get_daily_summary($date, $patient_id);

        // 섭취량과 배설량 합계 계산
        $intake_total = 0;
        $output_total = 0;

        foreach ($summary as $item) {
            if ($item['record_type'] === 'intake' && $item['unit'] === 'ml') {
                $intake_total += floatval($item['total_amount']);
            } elseif ($item['record_type'] === 'output' && $item['unit'] === 'ml') {
                $output_total += floatval($item['total_amount']);
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $summary,
            'totals' => array(
                'intake_ml' => $intake_total,
                'output_ml' => $output_total,
                'balance' => $intake_total - $output_total,
            ),
            'date' => $date,
        ), 200);
    }

    /**
     * 사용 가능한 날짜 목록
     */
    public function get_dates($request) {
        $patient_id = $request->get_param('patient_id') ?? 0;
        $dates = $this->database->get_available_dates($patient_id);

        // 오늘 날짜가 없으면 추가
        $today = date('Y-m-d');
        if (!in_array($today, $dates)) {
            array_unshift($dates, $today);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $dates,
        ), 200);
    }
}
