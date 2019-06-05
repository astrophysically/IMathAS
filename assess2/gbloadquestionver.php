<?php
/*
 * IMathAS: Gradebook - Get question version data for a student
 * (c) 2019 David Lippman
 *
 * Method: GET
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Student's User ID
 *  aver  The assessment version
 *  qn    The question number
 *  ver   The version # to get
 *  practice  true if the version is a practice version
 *
 * Returns: assessInfo.assess_versions[ver] object
 */

$init_skip_csrfp = true; // TODO: get CSRFP to work
$no_session_handler = 'json_error';
require_once("../init.php");
require_once("./common_start.php");
require_once("./AssessInfo.php");
require_once("./AssessRecord.php");
require_once('./AssessUtils.php');

header('Content-Type: application/json; charset=utf-8');

if (!$isActualTeacher && !$istutor) {
  echo '{"error": "no_access"}';
  exit;
}
//validate inputs
check_for_required('GET', array('aid', 'cid', 'uid', 'qn', 'ver', 'practice'));
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
$uid = Sanitize::onlyInt($_GET['uid']);
$qn = Sanitize::onlyInt($_GET['qn']);
$ver = Sanitize::onlyInt($_GET['ver']);
$practicever = ($_GET['practice'] == 1);

//load settings without questions
$assess_info = new AssessInfo($DBH, $aid, $cid, false);
if ($istutor) {
  $tutoredit = $assess_info->getSetting('tutoredit');
  if ($tutoredit == 2) { // no Access
    echo '{"error": "no_access"}';
    exit;
  }
}
if ($practicever) {
  $assess_info->overridePracticeSettings();
}
// load question settings and code
$assess_info->loadQuestionSettings('all', true);

//load user's assessment record - start with scored data
$assess_record = new AssessRecord($DBH, $assess_info, false);
$assess_record->loadRecord($uid);
if (!$assess_record->hasRecord()) {
  echo '{"error": "invalid_record"}';
  exit;
}
if ($practicever) {
  $assess_record->setInPractice(true);
}
// indicate whether teacher/tutor for viewing scores/answers
if ($isActualTeacher || ($istutor && $tutoredit == 1)) {
  $assess_record->setTeacherInGb(true);
}
$assess_record->parseData();

// get requested question version
$assessInfoOut = $assess_record->getGbQuestionVersionData($qn, true, $ver);

//output JSON object
echo json_encode($assessInfoOut);