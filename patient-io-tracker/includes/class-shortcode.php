<?php
/**
 * 숏코드 클래스
 */

if (!defined('ABSPATH')) {
    exit;
}

class PIO_Shortcode {

    /**
     * 생성자
     */
    public function __construct() {
        add_shortcode('patient_io_tracker', array($this, 'render_tracker'));
    }

    /**
     * 트래커 렌더링
     */
    public function render_tracker($atts) {
        $atts = shortcode_atts(array(
            'patient_id' => 0,
        ), $atts, 'patient_io_tracker');

        ob_start();
        ?>
        <div id="patient-io-tracker" data-patient-id="<?php echo esc_attr($atts['patient_id']); ?>">
            <!-- 헤더 -->
            <div class="pio-header">
                <h2 class="pio-title">환자 I/O 관리</h2>
            </div>

            <!-- 카테고리 버튼 영역 -->
            <div class="pio-categories">
                <!-- 섭취량 -->
                <div class="pio-category-group">
                    <h3 class="pio-group-title">섭취량 (Intake)</h3>
                    <div class="pio-buttons">
                        <button class="pio-btn pio-btn-intake" data-type="intake" data-category="water">
                            <span class="pio-icon">💧</span>
                            <span class="pio-label">물</span>
                        </button>
                        <button class="pio-btn pio-btn-intake" data-type="intake" data-category="meal">
                            <span class="pio-icon">🍚</span>
                            <span class="pio-label">식사</span>
                        </button>
                        <button class="pio-btn pio-btn-intake" data-type="intake" data-category="medicine">
                            <span class="pio-icon">💊</span>
                            <span class="pio-label">약</span>
                        </button>
                        <button class="pio-btn pio-btn-intake" data-type="intake" data-category="beverage">
                            <span class="pio-icon">🥤</span>
                            <span class="pio-label">음료</span>
                        </button>
                    </div>
                </div>

                <!-- 배설량 -->
                <div class="pio-category-group">
                    <h3 class="pio-group-title">배설량 (Output)</h3>
                    <div class="pio-buttons">
                        <button class="pio-btn pio-btn-output" data-type="output" data-category="urine">
                            <span class="pio-icon">🚽</span>
                            <span class="pio-label">소변</span>
                        </button>
                        <button class="pio-btn pio-btn-output" data-type="output" data-category="stool">
                            <span class="pio-icon">💩</span>
                            <span class="pio-label">대변</span>
                        </button>
                        <button class="pio-btn pio-btn-output" data-type="output" data-category="diarrhea">
                            <span class="pio-icon">💨</span>
                            <span class="pio-label">설사</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 일자별 탭 -->
            <div class="pio-date-tabs">
                <button class="pio-date-nav pio-date-prev">&lt;</button>
                <div class="pio-date-list"></div>
                <button class="pio-date-nav pio-date-next">&gt;</button>
            </div>

            <!-- 기록 목록 -->
            <div class="pio-records">
                <h3 class="pio-records-title">오늘의 기록</h3>
                <div class="pio-records-list"></div>
            </div>

            <!-- 일일 요약 -->
            <div class="pio-summary">
                <h3 class="pio-summary-title">일일 요약</h3>
                <div class="pio-summary-content">
                    <div class="pio-summary-item pio-summary-intake">
                        <span class="pio-summary-label">섭취량</span>
                        <span class="pio-summary-value" id="pio-intake-total">0 ml</span>
                    </div>
                    <div class="pio-summary-item pio-summary-output">
                        <span class="pio-summary-label">배설량</span>
                        <span class="pio-summary-value" id="pio-output-total">0 ml</span>
                    </div>
                    <div class="pio-summary-item pio-summary-balance">
                        <span class="pio-summary-label">밸런스</span>
                        <span class="pio-summary-value" id="pio-balance">0 ml</span>
                    </div>
                </div>
            </div>

            <!-- 입력 모달 -->
            <div class="pio-modal" id="pio-input-modal">
                <div class="pio-modal-overlay"></div>
                <div class="pio-modal-content">
                    <div class="pio-modal-header">
                        <h3 class="pio-modal-title">기록 입력</h3>
                        <button class="pio-modal-close">&times;</button>
                    </div>
                    <form class="pio-form" id="pio-record-form">
                        <input type="hidden" id="pio-record-type" name="record_type">
                        <input type="hidden" id="pio-category" name="category">

                        <div class="pio-form-group">
                            <label class="pio-form-label">
                                <span id="pio-category-icon"></span>
                                <span id="pio-category-label"></span>
                            </label>
                        </div>

                        <div class="pio-form-group">
                            <label class="pio-form-label" for="pio-amount">양</label>
                            <div class="pio-input-group">
                                <input type="number" id="pio-amount" name="amount" class="pio-input" required min="0" step="0.1">
                                <span class="pio-input-unit" id="pio-unit">ml</span>
                            </div>
                        </div>

                        <div class="pio-form-group">
                            <label class="pio-form-label">빠른 입력</label>
                            <div class="pio-quick-amounts"></div>
                        </div>

                        <div class="pio-form-group">
                            <label class="pio-form-label" for="pio-time">시간</label>
                            <input type="datetime-local" id="pio-time" name="recorded_at" class="pio-input">
                        </div>

                        <div class="pio-form-group">
                            <label class="pio-form-label" for="pio-memo">메모 (선택)</label>
                            <textarea id="pio-memo" name="memo" class="pio-input pio-textarea" rows="2"></textarea>
                        </div>

                        <div class="pio-form-actions">
                            <button type="button" class="pio-btn-cancel">취소</button>
                            <button type="submit" class="pio-btn-save">저장</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 로딩 오버레이 -->
            <div class="pio-loading" id="pio-loading">
                <div class="pio-spinner"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
