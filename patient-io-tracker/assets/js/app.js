/**
 * Patient I/O Tracker App
 */

(function($) {
    'use strict';

    // 앱 상태
    const state = {
        patientId: 0,
        currentDate: new Date().toISOString().split('T')[0],
        dates: [],
        records: [],
        categories: pioData.categories
    };

    // DOM 요소 캐시
    const elements = {
        container: null,
        dateList: null,
        recordsList: null,
        modal: null,
        form: null,
        loading: null
    };

    // 초기화
    function init() {
        elements.container = $('#patient-io-tracker');
        if (!elements.container.length) return;

        elements.dateList = elements.container.find('.pio-date-list');
        elements.recordsList = elements.container.find('.pio-records-list');
        elements.modal = $('#pio-input-modal');
        elements.form = $('#pio-record-form');
        elements.loading = $('#pio-loading');

        state.patientId = elements.container.data('patient-id') || 0;

        bindEvents();
        loadDates();
        loadRecords();
    }

    // 이벤트 바인딩
    function bindEvents() {
        // 카테고리 버튼 클릭
        elements.container.on('click', '.pio-btn', function() {
            const $btn = $(this);
            const type = $btn.data('type');
            const category = $btn.data('category');
            openModal(type, category);
        });

        // 모달 닫기
        elements.modal.on('click', '.pio-modal-close, .pio-modal-overlay, .pio-btn-cancel', function() {
            closeModal();
        });

        // 폼 제출
        elements.form.on('submit', function(e) {
            e.preventDefault();
            saveRecord();
        });

        // 빠른 입력 버튼
        elements.modal.on('click', '.pio-quick-btn', function() {
            const amount = $(this).data('amount');
            $('#pio-amount').val(amount);
        });

        // 날짜 탭 클릭
        elements.container.on('click', '.pio-date-tab', function() {
            const date = $(this).data('date');
            selectDate(date);
        });

        // 날짜 네비게이션
        elements.container.on('click', '.pio-date-prev', function() {
            navigateDates(-1);
        });

        elements.container.on('click', '.pio-date-next', function() {
            navigateDates(1);
        });

        // 기록 삭제
        elements.container.on('click', '.pio-record-delete', function() {
            const id = $(this).closest('.pio-record-item').data('id');
            if (confirm('이 기록을 삭제하시겠습니까?')) {
                deleteRecord(id);
            }
        });
    }

    // 날짜 목록 로드
    function loadDates() {
        $.ajax({
            url: pioData.restUrl + 'dates',
            method: 'GET',
            data: { patient_id: state.patientId },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', pioData.nonce);
            },
            success: function(response) {
                if (response.success) {
                    state.dates = response.data;
                    renderDateTabs();
                }
            }
        });
    }

    // 기록 로드
    function loadRecords() {
        showLoading();

        $.ajax({
            url: pioData.restUrl + 'records',
            method: 'GET',
            data: {
                date: state.currentDate,
                patient_id: state.patientId
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', pioData.nonce);
            },
            success: function(response) {
                if (response.success) {
                    state.records = response.data;
                    renderRecords();
                    loadSummary();
                }
            },
            complete: function() {
                hideLoading();
            }
        });
    }

    // 요약 로드
    function loadSummary() {
        $.ajax({
            url: pioData.restUrl + 'summary',
            method: 'GET',
            data: {
                date: state.currentDate,
                patient_id: state.patientId
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', pioData.nonce);
            },
            success: function(response) {
                if (response.success) {
                    renderSummary(response.totals);
                }
            }
        });
    }

    // 날짜 탭 렌더링
    function renderDateTabs() {
        const $dateList = elements.dateList;
        $dateList.empty();

        const today = new Date().toISOString().split('T')[0];

        // 최근 7일 표시
        const displayDates = [];
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            displayDates.push(date.toISOString().split('T')[0]);
        }

        displayDates.forEach(function(date) {
            const dateObj = new Date(date);
            const isToday = date === today;
            const isActive = date === state.currentDate;

            const label = isToday ? '오늘' : formatDateShort(dateObj);

            const $tab = $('<button>')
                .addClass('pio-date-tab')
                .addClass(isToday ? 'today' : '')
                .addClass(isActive ? 'active' : '')
                .attr('data-date', date)
                .text(label);

            $dateList.append($tab);
        });

        // 오늘 탭으로 스크롤
        const $activeTab = $dateList.find('.active');
        if ($activeTab.length) {
            $dateList.scrollLeft($activeTab.position().left);
        }
    }

    // 기록 렌더링
    function renderRecords() {
        const $list = elements.recordsList;
        $list.empty();

        if (state.records.length === 0) {
            $list.html('<div class="pio-no-records">기록이 없습니다</div>');
            return;
        }

        state.records.forEach(function(record) {
            const categoryInfo = getCategoryInfo(record.record_type, record.category);
            const time = formatTime(record.recorded_at);

            const $item = $('<div>')
                .addClass('pio-record-item')
                .addClass(record.record_type)
                .attr('data-id', record.id);

            $item.html(`
                <span class="pio-record-time">${time}</span>
                <span class="pio-record-icon">${categoryInfo.icon}</span>
                <div class="pio-record-info">
                    <div class="pio-record-category">${categoryInfo.label}</div>
                    <div class="pio-record-amount">${record.amount} ${record.unit}</div>
                    ${record.memo ? `<div class="pio-record-memo">${escapeHtml(record.memo)}</div>` : ''}
                </div>
                <button class="pio-record-delete" title="삭제">×</button>
            `);

            $list.append($item);
        });

        // 제목 업데이트
        const dateObj = new Date(state.currentDate);
        const today = new Date().toISOString().split('T')[0];
        const title = state.currentDate === today ? '오늘의 기록' : formatDateLong(dateObj) + ' 기록';
        elements.container.find('.pio-records-title').text(title);
    }

    // 요약 렌더링
    function renderSummary(totals) {
        $('#pio-intake-total').text(totals.intake_ml + ' ml');
        $('#pio-output-total').text(totals.output_ml + ' ml');

        const balance = totals.balance;
        const balanceText = (balance >= 0 ? '+' : '') + balance + ' ml';
        const $balanceEl = $('#pio-balance');
        $balanceEl.text(balanceText);

        // 밸런스 색상
        $balanceEl.closest('.pio-summary-balance')
            .toggleClass('negative', balance < 0);
    }

    // 모달 열기
    function openModal(type, category) {
        const categoryInfo = getCategoryInfo(type, category);

        // 폼 초기화
        elements.form[0].reset();
        $('#pio-record-type').val(type);
        $('#pio-category').val(category);
        $('#pio-category-icon').text(categoryInfo.icon);
        $('#pio-category-label').text(categoryInfo.label);
        $('#pio-unit').text(categoryInfo.unit);

        // 현재 시간 설정
        const now = new Date();
        const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
            .toISOString()
            .slice(0, 16);
        $('#pio-time').val(localDateTime);

        // 빠른 입력 버튼 설정
        const $quickAmounts = elements.modal.find('.pio-quick-amounts');
        $quickAmounts.empty();

        const quickValues = getQuickAmounts(category, categoryInfo.unit);
        quickValues.forEach(function(value) {
            $quickAmounts.append(
                $('<button>')
                    .attr('type', 'button')
                    .addClass('pio-quick-btn')
                    .attr('data-amount', value)
                    .text(value + ' ' + categoryInfo.unit)
            );
        });

        elements.modal.addClass('active');
        $('#pio-amount').focus();
    }

    // 모달 닫기
    function closeModal() {
        elements.modal.removeClass('active');
    }

    // 기록 저장
    function saveRecord() {
        const formData = {
            patient_id: state.patientId,
            record_type: $('#pio-record-type').val(),
            category: $('#pio-category').val(),
            amount: parseFloat($('#pio-amount').val()),
            unit: $('#pio-unit').text(),
            memo: $('#pio-memo').val(),
            recorded_at: $('#pio-time').val().replace('T', ' ') + ':00'
        };

        showLoading();

        $.ajax({
            url: pioData.restUrl + 'records',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', pioData.nonce);
            },
            success: function(response) {
                if (response.success) {
                    closeModal();
                    loadRecords();
                    loadDates();
                } else {
                    alert('저장에 실패했습니다.');
                }
            },
            error: function() {
                alert('저장에 실패했습니다.');
            },
            complete: function() {
                hideLoading();
            }
        });
    }

    // 기록 삭제
    function deleteRecord(id) {
        showLoading();

        $.ajax({
            url: pioData.restUrl + 'records/' + id,
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', pioData.nonce);
            },
            success: function(response) {
                if (response.success) {
                    loadRecords();
                } else {
                    alert('삭제에 실패했습니다.');
                }
            },
            error: function() {
                alert('삭제에 실패했습니다.');
            },
            complete: function() {
                hideLoading();
            }
        });
    }

    // 날짜 선택
    function selectDate(date) {
        state.currentDate = date;
        renderDateTabs();
        loadRecords();
    }

    // 날짜 네비게이션
    function navigateDates(direction) {
        const currentDate = new Date(state.currentDate);
        currentDate.setDate(currentDate.getDate() + direction);
        selectDate(currentDate.toISOString().split('T')[0]);
    }

    // 카테고리 정보 가져오기
    function getCategoryInfo(type, category) {
        const categories = state.categories[type];
        if (categories && categories[category]) {
            return categories[category];
        }
        return { label: category, icon: '📝', unit: '' };
    }

    // 빠른 입력 값 가져오기
    function getQuickAmounts(category, unit) {
        switch (unit) {
            case 'ml':
                return [50, 100, 150, 200, 250, 300, 500];
            case 'g':
                return [100, 200, 300, 400, 500];
            case '정':
                return [1, 2, 3];
            case '회':
                return [1, 2, 3];
            default:
                return [1, 2, 3, 5, 10];
        }
    }

    // 시간 포맷팅
    function formatTime(datetime) {
        const date = new Date(datetime);
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        return hours + ':' + minutes;
    }

    // 날짜 포맷팅 (짧은)
    function formatDateShort(date) {
        const month = date.getMonth() + 1;
        const day = date.getDate();
        return month + '/' + day;
    }

    // 날짜 포맷팅 (긴)
    function formatDateLong(date) {
        const month = date.getMonth() + 1;
        const day = date.getDate();
        return month + '월 ' + day + '일';
    }

    // HTML 이스케이프
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // 로딩 표시
    function showLoading() {
        elements.loading.addClass('active');
    }

    // 로딩 숨기기
    function hideLoading() {
        elements.loading.removeClass('active');
    }

    // DOM 준비 시 초기화
    $(document).ready(init);

})(jQuery);
