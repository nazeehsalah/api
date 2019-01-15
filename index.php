<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

require_once 'ripcord/ripcord.php';
$url = 'http://localhost:8069';
$db = "odoo-test";
//$username = 'nazeehsalah28@gmail.com';
//$password = '123456789';
//header("Content-type: text/json");
header("Access-Control-Allow-Origin:*");
//fun();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    if (
        isset($input["uid"]) && isset($input["password"])
        && isset($input["modalname"]) && isset($input["method"])
        && isset($input["parmlist"])
    ) {
        echo callMethods($input["uid"], $input["password"], $input["modalname"], $input["method"], $input["parmlist"], $input["mappinglist"]);
    } else {
        echo json_encode(array("error" => "invaild"));
    }
} else {
    if (isset($_GET["password"]) && isset($_GET["username"])) {
        login($_GET["username"], $_GET["password"]);
    } else if (
        isset($_GET["uid"])
        && isset($_GET["password"])
        && isset($_GET["modalname"])
        && isset($_GET["method"])
        //&& isset($_GET["parmlist"])
    ) {
        /*
         * next if condition to retrive data for graph as a list
         */
        if ((isset($_GET['months']) && count(json_decode($_GET['months']))) || isset($_GET['lob']) || isset($_GET['ins'])) {
            if (isset($_GET['lob']) && $_GET['lob'] == "true") {
                $lis = array();
                $lob = json_decode(callMethods($_GET["uid"], $_GET["password"], "insurance.line.business", "search_read", array(), array("fields" => array("line_of_business"))), true);
                for ($i = 0; $i < count($lob); $i++) {
                    $lis[$lob[$i]['line_of_business']] = callMethods($_GET["uid"], $_GET["password"], $_GET["modalname"], $_GET["method"], array(array(array("line_of_bussines", '=', $lob[$i]['id']))), $_GET["mappinglist"]);
                }
                echo json_encode($lis);
            } else if (isset($_GET['ins']) && $_GET['ins'] = "true") {
                $insReturn = array();
                $insList = json_decode(callMethods($_GET["uid"], $_GET["password"], "res.partner", "search_read", array(array(array("insurer_type", "=", true))), array("fields" => array("name"))), true);
                for ($i = 0; $i < count($insList); $i++) {
                    // $lis[$i]=$lob[$i]['id'];
                    $insReturn[$insList[$i]['name']] = callMethods($_GET["uid"], $_GET["password"], $_GET["modalname"], $_GET["method"], array(array(array("company", '=', $insList[$i]['id']))), $_GET["mappinglist"]);
                }
                echo json_encode($insReturn);
            } else {
                $index = count($_GET["parmlist"][0]);
                $monthes = json_decode($_GET['months'], true);
                $countList = [];
                for ($i = 0; $i < count($monthes); $i++) {
                    $newPar = array("create_date", "<", $monthes[$i]);
                    $_GET["parmlist"][0][$index + 0] = $newPar;
                    $newPar = array("create_date", ">", $monthes[$i + 1]);
                    $_GET["parmlist"][0][$index + 1] = $newPar;
                    $countList[$i] = callMethods($_GET["uid"], $_GET["password"], $_GET["modalname"], $_GET["method"], $_GET["parmlist"], $_GET["mappinglist"]);
                    if ($i + 2 == count($monthes)) {
                        /*  echo json_encode($i+2); */
                        break;
                    }
                }
                echo json_encode($countList);}
        } else {
            echo json_encode(array("error" => "invaild"));
        }

    } else {
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);
        echo json_encode(callMethods($input["uid"], $input["password"], $input["modalname"], $input["method"], $input["parmlist"]));
    }
}
/**
 * login
 * @param mixed $name
 * @param mixed $pass
 * @return mixed return user authenticate id
 */
function login($name, $pass)
{
    $url = $GLOBALS['url'];
    $common = ripcord::client("$url/xmlrpc/2/common");
    $common->version();
    $GLOBALS['username'] = $name;
    $GLOBALS['password'] = $pass;
    $uid = $common->authenticate($GLOBALS['db'], $GLOBALS['username'], $GLOBALS['password'], array());
    if ($uid) {
        echo json_encode(array("userId" => $uid));
    } else {
        echo json_encode(array('error' => "username or password wrong"));
    }
}
/**
 * callMethods
 * @param mixed $uid user authenticate id
 * @param mixed $password user
 * @param mixed $modelName odoo model name,
 * @param mixed $methodName odoo model method name
 * @param mixed $paramtersList ==> list of method paramter
 * @param  mixed $mappingParameters ==>a mapping/dict of parameters to pass by keyword (optional)
 * @return mixed
 */
function callMethods(
    $uid,
    $pass,
    $modelName,
    $methodName,
    $paramtersList = array(),
    $mappingParameters = array()) {
    foreach ($mappingParameters as $key => $value) {
        if (is_numeric($value)) {
            $mappingParameters[$key] = (int) $value;
        }
    }
    foreach ($paramtersList as $key => $value) {
        if (is_numeric($value)) {
            $paramtersList[$key] = (int) $value;
        }
    }
    $models = ripcord::client("$GLOBALS[url]/xmlrpc/2/object");
    $response = $models->execute_kw($GLOBALS['db'], (int) $uid, $pass, $modelName, $methodName, $paramtersList, $mappingParameters);
    return json_encode($response);
    // echo json_encode($response);
}
function fun()
{
    $models = ripcord::client("$GLOBALS[url]/xmlrpc/2/object");
    $response = $models->execute_kw($GLOBALS['db'], (int) '1', 'admin', 'crm.lead', 'read', array(), array('fields' => array("stage_id", 'planned_revenue')));
    echo json_encode($response);

}
