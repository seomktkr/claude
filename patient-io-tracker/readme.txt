=== Patient I/O Tracker ===
Contributors: healthcare-developer
Tags: patient, intake, output, medical, tracking, healthcare
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

환자의 섭취량(Intake)과 배설량(Output)을 관리하는 워드프레스 플러그인

== Description ==

Patient I/O Tracker는 환자의 일일 섭취량과 배설량을 기록하고 관리할 수 있는 워드프레스 플러그인입니다.

**섭취량 (Intake)**
* 물 (ml)
* 식사 (g)
* 약 (정)
* 음료 (ml)

**배설량 (Output)**
* 소변 (ml)
* 대변 (회)
* 설사 (회)

**주요 기능**
* 간편한 터치 기반 입력
* 일자별 기록 조회
* 일일 섭취/배설량 요약
* 밸런스(섭취-배설) 계산
* 모바일 친화적 UI

== Installation ==

1. `patient-io-tracker` 폴더를 `/wp-content/plugins/` 디렉토리에 업로드
2. 워드프레스 관리자 > 플러그인 메뉴에서 'Patient I/O Tracker' 활성화
3. 페이지에 숏코드 추가: `[patient_io_tracker]`

== Usage ==

기본 사용법:
`[patient_io_tracker]`

환자 ID 지정:
`[patient_io_tracker patient_id="123"]`

== Changelog ==

= 1.0.0 =
* 최초 릴리스
* 섭취량/배설량 기록 기능
* 일자별 조회 기능
* 일일 요약 기능
