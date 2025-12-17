<?php
//
// EXPORT EXCEL : NOTES + JUSTIFICATIONS
//

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/excellib.class.php');
require_once($CFG->dirroot . '/mod/peerwork/locallib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');

$id      = required_param('id', PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$type    = optional_param('type', '', PARAM_ALPHA);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'peerwork');
$peerwork = $DB->get_record('peerwork', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
require_sesskey();
$context = context_module::instance($cm->id);
require_capability('mod/peerwork:grade', $context);

// -----------------------------------------------------------------------------
// Groupes
// -----------------------------------------------------------------------------
if ($groupid) {
    $groupids = [$groupid];
} else {
    $groupids = array_keys(groups_get_all_groups(
        $course->id, 0, $peerwork->pwgroupingid
    ));
}
if (empty($groupids)) {
    throw new moodle_exception('nogroups', 'mod_peerwork');
}

// -----------------------------------------------------------------------------
// Fichier XLS
// -----------------------------------------------------------------------------
$filename = clean_filename($peerwork->name . '-export-' . $cm->id . '.xls');
$filename = preg_replace('/[^a-z0-9-_]+/i', '_', $filename);

$workbook = new MoodleExcelWorkbook($filename);
$workbook->send($filename);

// -----------------------------------------------------------------------------
// Utilitaires
// -----------------------------------------------------------------------------
function pw_sort_users(array $users): array {
    uasort($users, function($a, $b) {
        $ln = strcasecmp($a['lastname'], $b['lastname']);
        if ($ln !== 0) return $ln;
        return strcasecmp($a['firstname'], $b['firstname']);
    });
    return $users;
}

function pw_criterion_label($criteriaid, $rawdescription) {
    $raw = trim(html_to_text((string)$rawdescription));
    if ($raw !== '') {
        return $raw;
    }
    // Si pas de description, on indique au moins l'id (utile pour les justifs globales).
    return "Critère #{$criteriaid}";
}

// -----------------------------------------------------------------------------
// SQL Params
// -----------------------------------------------------------------------------
list($ingroupsql, $gparams) = $DB->get_in_or_equal($groupids, SQL_PARAMS_NAMED);
$params = ['peerworkid' => $peerwork->id] + $gparams;

// -----------------------------------------------------------------------------
// CHARGER LES NOTES
// -----------------------------------------------------------------------------
$sqlnotes = "
SELECT
    g.id AS groupid,
    g.name AS groupname,
    pp.criteriaid,
    c.description AS criterion,
    gb.id AS graderid,
    gb.firstname AS gfn,
    gb.lastname  AS gln,
    gf.id AS gradeeid,
    gf.firstname AS efn,
    gf.lastname  AS eln,
    pp.grade
FROM {peerwork_peers} pp
JOIN {peerwork} p               ON p.id = pp.peerwork
LEFT JOIN {peerwork_criteria} c ON c.id = pp.criteriaid
JOIN {groups} g                 ON g.id = pp.groupid
JOIN {user} gb                  ON gb.id = pp.gradedby
JOIN {user} gf                  ON gf.id = pp.gradefor
WHERE p.id = :peerworkid
  AND g.id $ingroupsql
ORDER BY g.name, c.sortorder, gln, gfn, eln, efn";

$rs = $DB->get_recordset_sql($sqlnotes, $params);

$groups = [];

foreach ($rs as $r) {

    $gid = $r->groupid;
    $cid = $r->criteriaid;

    if (!isset($groups[$gid])) {
        $groups[$gid] = [
            'groupname' => $r->groupname,
            'users'     => [],
            'criteria'  => [],
        ];
    }

    $groups[$gid]['users'][$r->graderid] = [
        'firstname'=>$r->gfn, 'lastname'=>$r->gln
    ];
    $groups[$gid]['users'][$r->gradeeid] = [
        'firstname'=>$r->efn, 'lastname'=>$r->eln
    ];

    if (!isset($groups[$gid]['criteria'][$cid])) {
        $groups[$gid]['criteria'][$cid] = [
            'criterion' => pw_criterion_label($cid, $r->criterion),
            'grades'    => [],
            'comments'  => [],
        ];
    }

    $groups[$gid]['criteria'][$cid]['grades'][$r->graderid][$r->gradeeid]
        = $r->grade;
}

$rs->close();

// -----------------------------------------------------------------------------
// CHARGER LES JUSTIFICATIONS
// -----------------------------------------------------------------------------
$sqljust = "
SELECT
    j.groupid,
    j.criteriaid,
    c.description AS criterion,
    j.gradedby AS graderid,
    gb.firstname AS gfn,
    gb.lastname  AS gln,
    j.gradefor  AS gradeeid,
    gf.firstname AS efn,
    gf.lastname  AS eln,
    j.justification
FROM {peerwork_justification} j
LEFT JOIN {peerwork_criteria} c ON c.id = j.criteriaid
JOIN {groups} g            ON g.id = j.groupid
JOIN {user} gb             ON gb.id = j.gradedby
JOIN {user} gf             ON gf.id = j.gradefor
WHERE j.peerworkid = :peerworkid
  AND g.id $ingroupsql
ORDER BY g.name, c.sortorder, gln, gfn, eln, efn";

$rsj = $DB->get_recordset_sql($sqljust, $params);

foreach ($rsj as $r) {

    $gid = $r->groupid;
    $cid = $r->criteriaid;

    $groups[$gid]['users'][$r->graderid] = [
        'firstname'=>$r->gfn, 'lastname'=>$r->gln
    ];
    $groups[$gid]['users'][$r->gradeeid] = [
        'firstname'=>$r->efn, 'lastname'=>$r->eln
    ];

    if (!isset($groups[$gid]['criteria'][$cid])) {
        $groups[$gid]['criteria'][$cid] = [
            'criterion'=>pw_criterion_label($cid, $r->criterion),
            'grades'=>[],
            'comments'=>[],
        ];
    }

    $just = trim(html_to_text($r->justification));
    if ($just !== '') {
        $groups[$gid]['criteria'][$cid]['comments'][$r->graderid][$r->gradeeid]
            = $just;
    }
}

$rsj->close();

// -----------------------------------------------------------------------------
// Trier les groupes
// -----------------------------------------------------------------------------
uksort($groups, function($a, $b) use ($groups) {
    return strcasecmp($groups[$a]['groupname'], $groups[$b]['groupname']);
});

// -----------------------------------------------------------------------------
// FEUILLE SOMMAIRE
// -----------------------------------------------------------------------------
$sheet =& $workbook->add_worksheet('Sommaire');
$sheet->write_string(0, 0, "Sommaire des groupes");
$row = 2;

foreach ($groups as $gid => $gdata) {

    $sn = mb_substr(
        preg_replace('/[\[\]\:\?\*\/\\\\]/','_',$gdata['groupname']),
        0, 31
    );

    $sheet->write_formula(
        $row++, 0,
        '=HYPERLINK("#\''.$sn.'\'!A1","'.$gdata['groupname'].'")'
    );
}

// -----------------------------------------------------------------------------
// FEUILLES PAR GROUPE
// -----------------------------------------------------------------------------
foreach ($groups as $gid => $gdata) {

    $sn = mb_substr(
        preg_replace('/[\[\]\:\?\*\/\\\\]/','_',$gdata['groupname']),
        0, 31
    );

    $sheet =& $workbook->add_worksheet($sn);
    $row = 0;

    $sheet->write_string($row++, 0, "Groupe : ".$gdata['groupname']);
    $row++;

    $users_sorted = pw_sort_users($gdata['users']);

    // -------------------------------------------------------------------------
    // I. NOTES
    // -------------------------------------------------------------------------
    foreach ($gdata['criteria'] as $cid => $cdata) {

        // ⚠️ Ne pas afficher les critères sans aucune note.
        if (empty($cdata['grades'])) {
            continue;
        }

        $sheet->write_string($row++, 0, "Critère : ".$cdata['criterion']);
        $row++;

        // En-têtes
        $sheet->write_string($row, 0, "Évaluateur");
        $col = 1;
        foreach ($users_sorted as $uid => $info) {
            $sheet->write_string($row, $col++, fullname((object)$info));
        }
        $row++;

        // Lignes
        foreach ($users_sorted as $graderid => $info) {
            $sheet->write_string($row, 0, fullname((object)$info));
            $col = 1;

            foreach ($users_sorted as $gradeeid => $info2) {
                $val = $cdata['grades'][$graderid][$gradeeid] ?? "";
                $sheet->write_string($row, $col++, (string)$val);
            }
            $row++;
        }

        $row += 2;
    }

    // -------------------------------------------------------------------------
    // II. JUSTIFICATIONS (tri global des évaluateurs)
    // -------------------------------------------------------------------------
    $sheet->write_string($row++, 0, "Justifications :");
    $row++;

    $allgraderids = array_keys($users_sorted); // déjà triés alphabétiquement

    foreach ($allgraderids as $graderid) {

        $graderinfo = $users_sorted[$graderid];

        $sheet->write_string(
            $row++,
            0,
            "— Évaluateur : ".fullname((object)$graderinfo)
        );

        // En-têtes du tableau
        $sheet->write_string($row, 0, "Évalué");
        $sheet->write_string($row, 1, "Justification");
        $row++;

        $has_any_comment = false;

        // Parcourir critères
        foreach ($gdata['criteria'] as $cid => $cdata) {

            $comments = $cdata['comments'][$graderid] ?? null;

            if (!empty($comments)) {
                $has_any_comment = true;

                foreach ($users_sorted as $gradeeid => $uinfo) {

                    $sheet->write_string($row, 0, fullname((object)$uinfo));

                    $just = $comments[$gradeeid] ?? "N/A";

                    $sheet->write_string($row, 1, $just);
                    $row++;
                }

                // $row += 1; // séparation entre critères pour cet évaluateur
            }
            
        }

        // Si aucune justification nulle part → tableau N/A
        if (!$has_any_comment) {

            foreach ($users_sorted as $gradeeid => $uinfo) {

                $sheet->write_string($row, 0, fullname((object)$uinfo));
                $sheet->write_string($row, 1, "N/A");
                $row++;
            }
        }

        $row += 2; // espace standard entre évaluateurs
    }
}

// -----------------------------------------------------------------------------
// FIN
// -----------------------------------------------------------------------------
$workbook->close();
exit;
