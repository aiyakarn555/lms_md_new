<?php
$title = 'รายงาน อัพเดทสถานะการเรียน';
$currentModel = 'Report';

ob_start();

$this->breadcrumbs = array($title);

Yii::app()->clientScript->registerScript(
    'updateGridView',
    <<<EOD
    $('.collapse-toggle').click();
    $('#Report_dateRang').attr('readonly','readonly');
    $('#Report_dateRang').css('cursor','pointer');
    $('#Report_type_cou').css('display','none');
EOD,
    CClientScript::POS_READY
);
?>
<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->baseUrl; ?>/css/bootstrap-chosen.css" />
<style type="text/css">
    .text-white {
        color: white;
    }
</style>
<script type="text/javascript" src="<?php echo Yii::app()->baseUrl; ?>/js/chosen.jquery.js"></script>
<?php
$arr = array(1 => 'เรียนรู้ด้วยตัวเอง', 2 => 'เรียนรู้ทางไกล');
?>

<div class="innerLR">

    <div class="widget">
        <div class="widget-head">
            <h4 class="heading glyphicons search">
                <i></i> ค้นหา:
            </h4>
        </div>
        <?php
        $form = $this->beginWidget(
            'CActiveForm',
            array(
                'action' => Yii::app()->createUrl($this->route),
                'method' => 'get',
            )
        );
        ?>
        <div class="widget-body">
            <dl class="dl-horizontal">
                <div class="form-group">
                    <dt><label>เลขบัตรประชาชน : </label></dt>
                    <dd>
                        <input style="width: 500px;" name="idcard" type="text" class="form-control" placeholder="เลขบัตรประชาชน" value="<?= $_GET['idcard'] ?>">
                    </dd>
                </div>

                <div class="form-group">
                    <dt><label>ชื่อ - นามสกุล : </label></dt>
                    <dd>
                        <input style="width: 500px;" name="nameSearch" type="text" class="form-control" placeholder="ชื่อ - นามสกุล" value="<?= $_GET['nameSearch'] ?>">
                    </dd>
                </div>

                <div class="form-group">
                    <dt></dt>
                    <dd><button type="submit" class="btn btn-primary btn-icon glyphicons search"><i></i> Search</button></dd>
                </div>
            </dl>
        </div>
        <?php $this->endWidget(); ?>
    </div>
</div>

<?php

$criteria = new CDbCriteria;
$criteria->with = array('pro', 'gen');

if (isset($_GET['idcard']) && $_GET['idcard'] != null) {
    $criteria->compare('pro.identification', $_GET['idcard'], true);
}

if (isset($_GET['nameSearch']) && $_GET['nameSearch'] != null) {
    $ex_fullname = explode(" ", $_GET['nameSearch']);
    if (isset($ex_fullname[0])) {
        $pro_fname = $ex_fullname[0];
        if (!preg_match('/[^A-Za-z]/', $pro_fname)) {
            $criteria->compare('pro.firstname_en', $pro_fname, true);
            $criteria->compare('pro.lastname_en', $pro_fname, true, 'OR');
        } else {
            $criteria->compare('pro.firstname', $pro_fname, true);
            $criteria->compare('pro.lastname', $pro_fname, true, 'OR');
        }
    }

    if (isset($ex_fullname[1])) {
        $pro_lname = $ex_fullname[1];
        if (!preg_match('/[^A-Za-z]/', $pro_lname)) {
            $criteria->compare('pro.lastname_en', $pro_lname, true);
        } else {
            $criteria->compare('pro.lastname', $pro_lname, true);
        }
    }
}

$allUsersLogStartCourse = LogStartMsTeams::model()->findAll($criteria);
$resultArr = [];
foreach ($allUsersLogStartCourse as $keyByUser => $valueByUser) {
    $resultArr[] = $valueByUser;
}

uasort($resultArr, function ($a, $b) {
    return $a['id'] <=> $b['id'];
});
// $result = array_column($resultArr, null, 'user_id');
$result = array_filter($resultArr, function ($v) {
    return !empty($v['user_id']);
});

usort($result, function ($a, $b) {
    return $a['id'] - $b['id'];
});

$allUsersLogStartCourse = $result;
$allUsersScoreMsTeams = array();

foreach ($allUsersLogStartCourse as $keyByUser => $valueByUser) {

    $criteria = new CDbCriteria;
    $criteria->with = array('manages');
    $criteria->compare("manage.active", "y");
    $criteria->compare("lessonteams.active", "y");
    $criteria->compare("ms_teams_id", $valueByUser->ms_teams_id);
    $criteria->compare("lang_id", "1");
    $criteria->order = "lesson_no ASC";
    $LessonMs = LessonMsTeams::model()->findAll($criteria);
    // var_dump($valueByUser->mem->institution_id);
    $allUsersScoreMsTeams[$keyByUser] = array(
        "idCard" => $valueByUser->pro->identification,
        "typeLearn" => 'เรียนรู้ทางไกล',
        "startCourseDate" => $valueByUser->create_date,
        "courseId" => $valueByUser->ms_teams_id,
        "title" => $valueByUser->pro->ProfilesTitle->prof_title,
        "userId" => $valueByUser->user_id,
        "genId" => $valueByUser->gen_id,
        "fName" => $valueByUser->pro->firstname,
        "lName" => $valueByUser->pro->lastname,
        "institutionName" => $valueByUser->msteams->createby->institution->institution_name,
        "courseTitle" => $valueByUser->msteams->name_ms_teams,
        "lessonScorePre" => array(),
        "lessonTotalPre" => array(),
        "lessonStatusPre" => array(),
        "lessonScorePost" => array(),
        "lessonTotalPost" => array(),
        "lessonStatusPost" => array(),
    );

    $CoursepassMs = null;
    if (count($LessonMs) > 0) {
        foreach ($LessonMs as $keyLessonMs => $valueLessonMs) {
            if (count($valueLessonMs->manages) > 0) {
                foreach ($valueLessonMs->manages as $manage) {
                    if ($manage->type == 'pre') {
                        //preTest
                        $criteria = new CDbCriteria;
                        $criteria->compare("ms_teams_id", $valueByUser->ms_teams_id);
                        $criteria->compare("lesson_teams_id", $valueLessonMs->id);
                        $criteria->compare("user_id", $valueByUser->user_id);
                        $criteria->compare("gen_id", $valueByUser->gen_id);
                        $criteria->compare("type", 'pre');
                        $criteria->compare("active", "y");
                        $criteria->order = "score_id DESC";
                        $ScoreMsPre = ScoreMsTeams::model()->find($criteria);

                        $allUsersScoreMsTeams[$keyByUser]["lessonScorePre"][] = ($ScoreMsPre) ? $ScoreMsPre->score_number : "-";
                        $allUsersScoreMsTeams[$keyByUser]["lessonTotalPre"][] = ($ScoreMsPre) ? $ScoreMsPre->score_total : "-";
                        $allUsersScoreMsTeams[$keyByUser]["lessonStatusPre"][] = ($ScoreMsPre) ? $ScoreMsPre->score_past : "-";
                        //preTes
                        $CoursepassMs =  $ScoreMsPre->update_date;
                    }
                    if ($manage->type == 'post') {
                        //postTest
                        $criteria = new CDbCriteria;
                        $criteria->compare("ms_teams_id", $valueByUser->ms_teams_id);
                        $criteria->compare("lesson_teams_id", $valueLessonMs->id);
                        $criteria->compare("user_id", $valueByUser->user_id);
                        $criteria->compare("gen_id", $valueByUser->gen_id);
                        $criteria->compare("type", 'post');
                        $criteria->compare("active", "y");
                        $criteria->order = "score_id DESC";
                        $ScoreMsPost = ScoreMsTeams::model()->find($criteria);
                        $allUsersScoreMsTeams[$keyByUser]["lessonScorePost"][] = ($ScoreMsPost) ? $ScoreMsPost->score_number : "-";
                        $allUsersScoreMsTeams[$keyByUser]["lessonTotalPost"][] = ($ScoreMsPost) ? $ScoreMsPost->score_total : "-";
                        $allUsersScoreMsTeams[$keyByUser]["lessonStatusPost"][] = ($ScoreMsPre) ? $ScoreMsPre->score_past : "-";
                        //postTest
                        $CoursepassMs =  $ScoreMsPost->update_date;
                    }
                }
            }
        }
    }
    $allUsersScoreMsTeams[$keyByUser]["passCourseDate"] = $CoursepassMs != null ?  $CoursepassMs : date("Y-m-d H:i:s");
}

$allUsersScoreCourse = array();
$criteria = new CDbCriteria;
$courseOnline = CourseOnline::model()->find($criteria);
$criteria = new CDbCriteria;
$criteria->with = array('pro', 'course', 'gen');

if (isset($_GET['idcard']) && $_GET['idcard'] != null) {
    $criteria->compare('pro.identification', $_GET['idcard'], true);
}
if (isset($_GET['nameSearch']) && $_GET['nameSearch'] != null) {
    $ex_fullname = explode(" ", $_GET['nameSearch']);
    if (isset($ex_fullname[0])) {
        $pro_fname = $ex_fullname[0];
        if (!preg_match('/[^A-Za-z]/', $pro_fname)) {
            $criteria->compare('pro.firstname_en', $pro_fname, true);
            $criteria->compare('pro.lastname_en', $pro_fname, true, 'OR');
        } else {
            $criteria->compare('pro.firstname', $pro_fname, true);
            $criteria->compare('pro.lastname', $pro_fname, true, 'OR');
        }
    }

    if (isset($ex_fullname[1])) {
        $pro_lname = $ex_fullname[1];
        if (!preg_match('/[^A-Za-z]/', $pro_lname)) {
            $criteria->compare('pro.lastname_en', $pro_lname, true);
        } else {
            $criteria->compare('pro.lastname', $pro_lname, true);
        }
    }
}

if ($courseOnline->price == "y" || $courseOnline->document_status == "y") {
    $userAllPayment = array();
    $criteriaCourseTemp = new CDbCriteria;
    if ($courseOnline->price == "y") {
        $criteriaCourseTemp->compare("status_payment", "y");
    }
    if ($courseOnline->document_status == "y") {
        $criteriaCourseTemp->compare("status_document", "y");
    }
    $CourseTemp = CourseTemp::model()->findAll($criteriaCourseTemp);
    foreach ($CourseTemp as $keyCourseTemp => $valueCourseTemp) {
        $userAllPayment[] = $valueCourseTemp->user_id;
    }
    $criteria->addInCondition("t.user_id", $userAllPayment);
}
$criteria->compare("t.active", 'y');
$allUsersLogStartCourse = LogStartcourse::model()->findAll($criteria);
$resultArr = [];
foreach ($allUsersLogStartCourse as $keyByUser => $valueByUser) {
    $resultArr[] = $valueByUser;
}


$result = array_column($resultArr, null, 'user_id');
$result = array_filter($result, function ($v) {
    return !empty($v['user_id']);
});


$allUsersLogStartCourse = $result;
foreach ($allUsersLogStartCourse as $keyByUser => $valueByUser) {
    $allUsersScoreCourse[$keyByUser] = array(
        "id" => $keyByUser + 1,
        "typeLearn" => 'เรียนรู้ด้วยตัวเอง',
        "courseId" => $valueByUser->course->course_id,
        "startCourseDate" => $valueByUser->create_date,
        "userId" => $valueByUser->pro->user_id,
        "genId" => $valueByUser->gen_id,
        "idCard" => $valueByUser->pro->identification,
        "title" => $valueByUser->pro->ProfilesTitle->prof_title,
        "fName" => $valueByUser->pro->firstname,
        "lName" => $valueByUser->pro->lastname,
        "institutionName" => $valueByUser->course->usercreate->institution->institution_name,
        "courseTitle" => $valueByUser->course->course_title,
        "lessonScorePre" => array(),
        "lessonTotalPre" => array(),
        "lessonStatusPre" => array(),
        "lessonScorePost" => array(),
        "lessonTotalPost" => array(),
        "lessonStatusPost" => array(),
        "courseScorePre" => array(),
        "courseTotalPre" => array(),
        "courseStatusPre" => array(),
        "courseScorePost" => array(),
        "courseTotalPost" => array(),
        "courseStatusPost" => array(),
        "lessonStatusLearn" => array(),
    );

    $courseManage = Coursemanage::Model()->findAll("id=:course_id AND active=:active ", array(
        "course_id" => $valueByUser->course_id, "active" => "y"
    ));



    $CoursedatepassNew = null;
    $criteria = new CDbCriteria;
    $criteria->compare("lesson.active", "y");
    $criteria->compare("course_id", $valueByUser->course_id);
    $criteria->compare("lang_id", "1");
    $criteria->order = "lesson_no ASC";
    $Lesson = Lesson::model()->findAll($criteria);
    if (count($Lesson) > 0) {
        $checkLesson = true;
        foreach ($Lesson as $keyLesson => $valueLesson) {
            $manages = Manage::Model()->findAll("id=:id AND active=:active ", array(
                "id" => $valueLesson->id, "active" => "y"
            ));

            if ($valueLesson->GetfileCount($valueLesson->id) > 0) {
                $criteria = new CDbCriteria;
                $criteria->compare("course_id", $valueByUser->course_id);
                $criteria->compare("lesson_id", $valueLesson->id);
                $criteria->compare("user_id", $valueByUser->user_id);
                $criteria->compare("gen_id", $valueByUser->gen_id);
                $criteria->compare("lesson_status", 'pass');
                $criteria->compare("lesson_active", "y");
                $Learns = Learn::model()->Count($criteria);
                $allUsersScoreCourse[$keyByUser]["lessonStatusLearn"][] = $Learns;
                $CoursedatepassNew = ($Learns) ? $Learns->learn_date : date("Y-m-d H:i:s");
            }
            if (count($manages) > 0) {
                foreach ($manages as $manage) {
                    if ($manage->type == 'pre') {
                        //preTest
                        $criteria = new CDbCriteria;
                        $criteria->compare("course_id", $valueByUser->course_id);
                        $criteria->compare("lesson_id", $valueLesson->id);
                        $criteria->compare("user_id", $valueByUser->user_id);
                        $criteria->compare("gen_id", $valueByUser->gen_id);
                        $criteria->compare("type", 'pre');
                        $criteria->compare("active", "y");
                        $criteria->order = "score_id DESC";
                        $ScorePre = Score::model()->find($criteria);
                        $allUsersScoreCourse[$keyByUser]["lessonScorePre"][] = ($ScorePre) ? $ScorePre->score_number : "-";
                        $allUsersScoreCourse[$keyByUser]["lessonTotalPre"][] = ($ScorePre) ? $ScorePre->score_total : "-";
                        $allUsersScoreCourse[$keyByUser]["lessonStatusPre"][] = ($ScorePre) ? $ScorePre->score_past : "-";
                        //preTest
                        $CoursedatepassNew = ($ScorePre) ? $ScorePre->update_date : date("Y-m-d H:i:s");
                    } else if ($manage->type == 'post') {
                        //postTest
                        $criteria = new CDbCriteria;
                        $criteria->compare("course_id", $valueByUser->course_id);
                        $criteria->compare("lesson_id", $valueLesson->id);
                        $criteria->compare("user_id", $valueByUser->user_id);
                        $criteria->compare("gen_id", $valueByUser->gen_id);
                        $criteria->compare("type", 'post');
                        $criteria->compare("active", "y");
                        $criteria->order = "score_id DESC";
                        $ScorePost = Score::model()->find($criteria);
                        $allUsersScoreCourse[$keyByUser]["lessonScorePost"][] = ($ScorePost) ? $ScorePost->score_number : "-";
                        $allUsersScoreCourse[$keyByUser]["lessonTotalPost"][] = ($ScorePost) ? $ScorePost->score_total : "-";
                        $allUsersScoreCourse[$keyByUser]["lessonStatusPost"][] = ($ScorePost) ? $ScorePost->score_past : "-";
                        $CoursedatepassNew = ($ScorePost) ? $ScorePost->update_date : date("Y-m-d H:i:s");
                        // postTest
                    }
                }
            }
        }
    }

    if (count($courseManage) > 0) {
        $checkCourse = true;
        foreach ($courseManage as $keyCourseManage => $valueCourseManage) {
            if ($valueCourseManage->type == 'pre') {
                //preTest
                $criteria = new CDbCriteria;
                $criteria->compare("course_id", $valueByUser->course_id);
                $criteria->compare("user_id", $valueByUser->user_id);
                $criteria->compare("gen_id", $valueByUser->gen_id);
                $criteria->compare("type", 'pre');
                $criteria->compare("active", "y");
                $criteria->order = "score_id DESC";
                $ScorePre = Coursescore::model()->find($criteria);
                $allUsersScoreCourse[$keyByUser]["courseScorePre"] = ($ScorePre) ? $ScorePre->score_number : "-";
                $allUsersScoreCourse[$keyByUser]["courseTotalPre"] = ($ScorePre) ? $ScorePre->score_total : "-";
                $allUsersScoreCourse[$keyByUser]["courseStatusPre"] = ($ScorePre) ? $ScorePre->score_past : "-";
                //preTest
                $CoursedatepassNew = ($ScorePre) ? $ScorePre->update_date : date("Y-m-d H:i:s");
            } else if ($valueCourseManage->type == 'course') {
                //postTest
                $criteria = new CDbCriteria;
                $criteria->compare("course_id", $valueByUser->course_id);
                $criteria->compare("user_id", $valueByUser->user_id);
                $criteria->compare("gen_id", $valueByUser->gen_id);
                $criteria->compare("type", 'post');
                $criteria->compare("active", "y");
                $criteria->order = "score_id DESC";
                $ScorePost = Coursescore::model()->find($criteria);
                $allUsersScoreCourse[$keyByUser]["courseScorePost"] = ($ScorePost) ? $ScorePost->score_number : "-";
                $allUsersScoreCourse[$keyByUser]["courseTotalPost"] = ($ScorePost) ? $ScorePost->score_total : "-";
                $allUsersScoreCourse[$keyByUser]["courseStatusPost"] = ($ScorePost) ? $ScorePost->score_past : "-";
                // postTest
                $CoursedatepassNew = ($ScorePost) ? $ScorePost->update_date : date("Y-m-d H:i:s");
            }
        }
    }
    $criteria = new CDbCriteria;
    $criteria->compare("passcours_cours", $valueByUser->course_id);
    $criteria->compare("passcours_user", $valueByUser->user_id);
    $criteria->compare("gen_id", $valueByUser->gen_id);
    $Coursepass = Passcours::model()->find($criteria);

    $allUsersScoreCourse[$keyByUser]["passCourseDate"] = $Coursepass != null ?  $Coursepass->passcours_date : $CoursedatepassNew;
}

foreach ($allUsersScoreCourse as $key => $value) {
    if (!$value["userId"]) {
        unset($allUsersScoreCourse[$key]);
    }
}

$allUsersScoreSet = array_merge($allUsersScoreCourse, $allUsersScoreMsTeams);
uasort($allUsersScoreSet, function ($a, $b) {
    return $a['idCard'] <=> $b['idCard'];
});

$allUsersScore = $allUsersScoreSet;
// $allUsersScoreView = $allUsersScoreSet;

// if (
//     isset($_GET['idCardView']) && isset($_GET['TypeCouView'])  && $_GET['TypeCouView'] != ""
//     && $_GET['CourseView'] != "" && $_GET['CourseView'] != "" && $_GET['GenView'] != "" && $_GET['GenView'] != ""
// ) {
//     $allUsersScoreView = array_filter($allUsersScoreView, function ($v) {
//         return $v['idCard'] == $_GET['idCardView'] && $v['typeLearn'] == $_GET['TypeCouView']
//             && $v['courseId'] == $_GET['CourseView'] && $v['genId'] == $_GET['GenView'];
//     });
// }

?>

<?php if (count($allUsersScore) > 0) { ?>

    <div class="widget" id="export-table33">

        <div class="widget-head">
            <div class="widget-head">
                <h4 class="heading glyphicons show_thumbnails_with_lines"><i></i>ค้นหาโดยใช้ หลักสูตร</h4>
            </div>
        </div>

        <div class="widget-body" style="overflow-x:auto;">
            <button type="button" onClick="reloaduser()" class="btn btn-primary btn-icon glyphicons refresh" style="float:right;" fdprocessedid="gb5c2b"><i></i> รีเฟรชรายชื่อ</button>
            <table class="table table-bordered table-striped" id="myTablePass">
                <thead>
                    <tr>
                        <th class="center text-white" rowspan="2">สถานะ</th>
                        <th class="center text-white" rowspan="2">เลขบัตรประชาชน</th>
                        <th class="center text-white" rowspan="2">ชื่อ - นามสกุล</th>
                        <th class="center text-white" rowspan="2">ชื่อสถาบันศึกษา</th>
                        <th class="center text-white" rowspan="2">ประเภทหลักสูตร</th>
                        <th class="center text-white" rowspan="2">ชื่อหลักสูตร</th>
                        <th class="center text-white" rowspan="2">วันที่เริ่ม - วันที่จบ</th>
                        <th class="center text-white" rowspan="2"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (isset($allUsersScore) && count($allUsersScore) > 0) {


                        foreach ($allUsersScore as $i => $val) {
                            $type_cou = 1;
                            if ($val["typeLearn"] == "เรียนรู้ทางไกล") {
                                $type_cou = 2;
                            }
                    ?>

                            <?php $coruse_percents =  Helpers::lib()->percent_CourseGenReport($val['courseId'], $val['genId'], $val["userId"]);
                            $course_check_approve =  Helpers::lib()->course_check_approve($val['courseId'], $val['genId'], $val["userId"]);
                            ?>
                            <?php if ($coruse_percents >= 100 && $course_check_approve) {  ?>
                                <tr>
                                    <td class="center"><button type="submit" class="scroll-view btn btn-success btn-icon" onClick="confirm_Approve('<?= $type_cou ?>','<?= $val['courseId'] ?>','<?= $val['genId'] ?>','<?= $val['userId'] ?>')" fdprocessedid="gb5c2b"><i></i> Approve</button></td>
                                    <td><?= $val["idCard"] ?></td>
                                    <td><?= $val["title"] . " " . $val["fName"] . " " . $val["lName"] ?></td>
                                    <td><?= $val["institutionName"] ?></td>
                                    <td><?= $val["typeLearn"] ?></td>
                                    <td><?= $val["courseTitle"] ?></td>
                                    <td><?= $val['startCourseDate'] . " - " . $val['passCourseDate'] ?></td>
                                    <td class="center"><button type="submit" onClick="scrollview('<?= $val["idCard"] ?>','<?= $val["typeLearn"] ?>','<?= $val["courseId"] ?>','<?= $val["genId"] ?>','<?= $val["fName"] ?>')" class="scroll-view btn btn-warning btn-icon glyphicon glyphicon-eye-open" fdprocessedid="gb5c2b"><i></i> ดูข้อมูล</button></td>
                                </tr>
                            <?php }
                            $ms_team_percents =  Helpers::lib()->percent_MsTeams($val['courseId'], $val['genId'], $val["userId"]);
                            $msteam_check_approve =  Helpers::lib()->msteam_check_approve($val['courseId'], $val['genId'], $val["userId"]);
                            ?>
                            <?php if ($ms_team_percents >= 100 && $msteam_check_approve) {  ?>
                                <tr>
                                    <td class="center"><button type="submit" class="scroll-view btn btn-success btn-icon" onClick="confirm_Approve('<?= $type_cou ?>','<?= $val['courseId'] ?>','<?= $val['genId'] ?>','<?= $val['userId'] ?>')" fdprocessedid="gb5c2b"><i></i> Approve</button></td>
                                    <td><?= $val["idCard"] ?></td>
                                    <td><?= $val["title"] . " " . $val["fName"] . " " . $val["lName"] ?></td>
                                    <td><?= $val["institutionName"] ?></td>
                                    <td><?= $val["typeLearn"] ?></td>
                                    <td><?= $val["courseTitle"] ?></td>
                                    <td><?= $val['startCourseDate'] . " - " . $val['passCourseDate'] ?></td>
                                    <td class="center"><button type="submit" onClick="scrollview('<?= $val["idCard"] ?>','<?= $val["typeLearn"] ?>','<?= $val["courseId"] ?>','<?= $val["genId"] ?>','<?= $val["fName"] ?>')" class="scroll-view btn btn-warning btn-icon glyphicon glyphicon-eye-open" fdprocessedid="gb5c2b"><i></i> ดูข้อมูล</button></td>
                                </tr>
                    <?php }
                            $start_cnt++;
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="widget-body" id="viewhight" style="overflow-x:auto;display: none;">

        </div>
    </div>

    </div>
<?php }  ?>

<script type="text/javascript">
    function confirm_Approve(type, id, gen_id, user_id) {

        swal({
                title: "แจ้งเตือน",
                text: "ยืนยันการ Approve",
                showCancelButton: true,
                allowEnterKey: true,
                closeOnConfirm: false,
                confirmButtonText: "ตกลง",
                cancelButtonText: "ยกเลิก",
                animation: "slide-from-top",
            },
            function() {
                $.ajax({
                    type: 'POST',
                    url: '<?php echo Yii::app()->createAbsoluteUrl("/Report/ApproveExamResult"); ?>',
                    data: ({
                        type: type,
                        gen_id: gen_id,
                        id: id,
                        user_id: user_id
                    }),
                    success: function(data) {
                        window.location = window.location.href.split("?")[0];
                        // location.reload();
                    }
                });
            });
    }

    $(document).ready(function() {

        <?php if (
            isset($_GET['idCardView']) && isset($_GET['TypeCouView'])  && $_GET['TypeCouView'] != ""
            && $_GET['CourseView'] != "" && $_GET['CourseView'] != "" && $_GET['GenView'] != "" && $_GET['GenView'] != ""
        ) { ?>


            // $('html,body').animate({
            //         scrollTop: $("#viewhight").offset().top
            //     },
            //     'slow');
        <?Php  } ?>
    });

    function scrollview(idcard, typeCou, course, gen , fname) {
        $.ajax({
            type: 'POST',
            url: '<?php echo Yii::app()->createAbsoluteUrl("/Report/SetTrStudyStatus"); ?>',
            data: ({
                idcard: idcard,
                nameSearch: fname,
                idCardView: idcard,
                TypeCouView: typeCou,
                CourseView: course,
                GenView: gen
            }),
            success: function(data) {
                $('#viewhight').html(data);

                $("#viewhight").css({
                    display: "block"
                });

                $('html,body').animate({
                        scrollTop: $("#viewhight").offset().top
                    },
                    'slow');

            }
        });


        // window.location = window.location.href.split("?")[0] + "?idcard=<?= $_GET['idcard'] ?>&nameSearch=<?= $_GET['nameSearch'] ?>&idCardView=" + idcard + "&TypeCouView=" + typeCou + "&CourseView=" + course + "&GenView=" + gen + "";
    }

    function reloaduser() {
        window.location = window.location.href.split("?")[0];
    }
</script>

</div>