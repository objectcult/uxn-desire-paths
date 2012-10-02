<?php
// field ajax requests for database activity

// {'action': "insert", 'type': "tool", 'username': uname, 'activity_date': actdate, 'tool_ref_id1': tid, 'difficulty': 2, 'worth': 2}
// so far, we're assuming actions on activity table
require_once("_functions.php");
$data = array();
$data['status'] = $__RETURN_CODES['QUERY FAIL'];

switch ($_GET['action']) {
	case 'insert':
		$res = addActivity($_GET['username'],$_GET['activity_date'],$_GET['type'],$_GET['tool_id_ref1'],$_GET['tool_id_ref2'],$_GET['difficulty'],$_GET['worth'],$_GET['note']);
		$data['status'] = $res;
		if ($res>0) {  // success
			$arec = array();
			$arec['activity_id'] = $res;
			foreach ($_GET as $k => $v) {
				$arec[$k] = $v;
			}
			$data['activity_record']=$arec;
		}
		break;
	case 'update':
		$res = updateActivity($_GET['activity_id'],$_GET['difficulty'],$_GET['worth'],$_GET['note']);
		$data['status'] = $res;
		if ($res>0) {  // success
			$data['activity_id'] = $res;
			foreach ($_GET as $k => $v) {
				$data[$k] = $v;
			}
		}
		break;
	case 'delete':
		$res = deleteActivities($_GET['activity_ids']);  // note plural==csv list; function returns an array of deleted IDs
		if (is_array($res)) {
			$data['status'] = count($res);
			$data['type'] = $_GET['type'];
			$data['activity_ids']=$res;
		} else {
			$data['status'] = $res;
		}
		break;
	case 'tool_get':
		$res = getTools($_GET['tool_id']);
		if (is_array($res)) {
			$data['status'] = count($res);
			$data['tool_name'] = $res[0]['tool_name'];
			$data['description'] = $res[0]['description'];
		} else {
			$data['status'] = $res;
		}
		break;
	case 'tool_add':
		$data['status'] = addTool($_GET['tool_name'], $_GET['description'], $_GET['added_by']);
		$data['tool_name'] = $_GET['tool_name'];
		break;
	case 'tool_update':
		$data['status'] = updateTool($_GET['tool_id'],$_GET['tool_name'], $_GET['description']);
		$data['tool_name'] = $_GET['tool_name'];
		break;
	default:
		break;
}
echo json_encode($data);

?>