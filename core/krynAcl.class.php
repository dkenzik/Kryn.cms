<?php

/*
 * This file is part of Kryn.cms.
 *
 * (c) Kryn.labs, MArc Schmidt <marc@kryn.org>
 *
 * To get the full copyright and license informations, please view the
 * LICENSE file, that was distributed with this source code.
 *
 */


class krynAcl {


    public static $cache = array();


    public static $acls = array();

    /**
     *
     * Mode table:
     *
     *  0 all
     *  1 list
     *  2 view
     *  3 add
     *  4 update
     *  5 delete
     *
     *
     * @static
     * @param $pObjectKey
     * @param int $pMode
     * @param bool $pForce
     * @return mixed
     *
     */
    public static function &getRules($pObjectKey, $pMode = 1, $pForce = false) {

        if (self::$cache[$pObjectKey.'_'.$pMode] && $pForce == false)
            return self::$cache[$pObjectKey.'_'.$pMode];

        $userRsn = kryn::$client->user_rsn;
        $inGroups = kryn::$client->user['inGroups'];

        $pObjectKey = esc($pObjectKey);
        $pMode += 0;

        $query = "
                SELECT constraint_type, constraint_code, mode, access, sub, fields FROM %pfx%system_acl
                WHERE
                object = ? AND
                (mode = ? OR mode = 0) AND
                (
                    ( target_type = 1 AND target_rsn IN (?))
                    OR
                    ( target_type = 2 AND target_rsn = ?)
                )
                ORDER BY prio DESC
        ";

        $res = dbExec($query, array($pObjectKey, $pMode, $inGroups, $userRsn));
        $rules = array();

        while ($rule = dbFetch($res)){
            if ($rule['fields'] && substr($rule['fields'], 0, 1) == '{'){
                $rule['fields'] = json_decode($rule['fields'], true);
            }
            if ($rule['constraint_type'] == 2 && substr($rule['constraint_code'], 0, 1) == '['){
                $rule['constraint_code'] = json_decode($rule['constraint_code'], true);
            }
            $rules[] = $rule;
        }

        self::$cache[$pObjectKey.'_'.$pMode] = $rules;
        return self::$cache[$pObjectKey.'_'.$pMode];
    }


    public static function getSqlCondition($pObjectKey, $pTable = ''){

        $rules =& self::getRules($pObjectKey, 1);

        if (count($rules) == 0) return '1=0';

        if (self::$cache['sqlList_' . $pObjectKey])
            return self::$cache['sqlList_' . $pObjectKey];

        $condition = '';
        $result = '';

        $primaryList = krynObjects::getPrimaryList($pObjectKey);
        $primaryKey = current($primaryList);

        $isNested = kryn::$objects[$pObjectKey]['nested'];
        $table = dbQuote(dbTableName(kryn::$objects[$pObjectKey]['table']));

        $lastBracket = '';

        $allowList = '';
        $denyList  = '';

        foreach($rules as $rule){

            if ($rule['constraint_type'] == '1' ){
                $condition = dbQuote($primaryKey, $pTable) . ' = ' . $rule['constraint_code'];
                if ($isNested && $rule['sub']){
                    $sCondition = dbQuote($primaryKey) . ' = ' . $rule['constraint_code'];
                    $sub  = "(lft > (SELECT lft FROM $table WHERE $sCondition) AND ";
                    $sub .= "rgt < (SELECT rgt FROM $table WHERE $sCondition))";
                    $condition = "($condition OR $sub)";
                }
            }

            if ($rule['constraint_type'] == '2'){
                $condition = dbConditionToSql($rule['constraint_code'], $pTable);
                if ($isNested && $rule['sub']){
                    $sCondition = dbConditionToSql($rule['constraint_code']);
                    $sub  = "(lft > (SELECT lft FROM $table WHERE $sCondition ORDER BY lft ) AND ";
                    $sub .= "rgt < (SELECT rgt FROM $table WHERE $sCondition ORDER BY rgt DESC))";
                    $condition = "($condition OR $sub)";
                }
            }

            if ($rule['constraint_type'] == '0')
                $condition = ' 1=1';


            if ($rule['access'] == 1){

                if ($result) $result .= ")\n\nOR\n";

                $result .= "\n(";

                $result .= $condition;

                if ($denyList)
                    $result .= ' AND NOT '.$denyList;

            }

            if ($rule['access'] != 1){

                $denyList .= ($denyList==''?'':' AND NOT ').$condition;
                if ($rule['sub']){
                    //$denyList .= ' AND NOT ';
                }
            } else
                $allowList .= ($allowList==''?'':' OR ').$condition;

        }

        $result .= ')';

        return "\n(\n$result\n)\n";

        return array($fields, $condition);

    }

    public static function checkList($pObjectKey, $pObjectId, $pField = false){
        return self::check($pObjectKey, $pObjectId, $pField, 1);
    }

    public static function checkRead($pObjectKey, $pObjectId, $pField = false){
        return self::check($pObjectKey, $pObjectId, $pField, 2);
    }

    public static function checkAdd($pObjectKey, $pObjectId, $pField = false){
        return self::check($pObjectKey, $pObjectId, $pField, 3);
    }

    public static function checkUpdate($pObjectKey, $pObjectId, $pField = false){
        return self::check($pObjectKey, $pObjectId, $pField, 3);
    }

    /**
     * @static
     * @param $pObjectKey
     * @param $pObjectId
     * @param bool|string|array $pField
     * @param int $pMode
     * @param bool $pRootHasAccess
     * @return bool
     */
    public static function check($pObjectKey, $pObjectId, $pField = false, $pMode = 1, $pRootHasAccess = false) {

        if (kryn::$client->id == 1)

        $rules =& self::getRules($pObjectKey, $pMode);

        if (count($rules) == 0) return false;

        if (self::$cache['checkAckl_' . $pObjectKey . '_' . $pObjectId . '__' . $pField])
            return self::$cache['checkAckl_' . $pObjectKey . '_' . $pObjectId . '__' . $pField];

        $access = false;

        $current_code = $pObjectId;

        $definition =& kryn::$objects[$pObjectKey];
        $fields =& $definition['fields'];


        $not_found = true;
        $parent_acl = false;
        $objectItem = false;
        $codes = array();


        $fIsArray = is_array($pField);
        if ($fIsArray){
            $fCount   = count($pField);

            $fKey   = key($pField);
            $fValue = current($pField);
        }


        $depth = 0;
        while ($not_found) {
            $depth++;

            if ($depth > 50) {
                $not_found = false;
                break;
            }

            foreach ($rules as $acl){

                if ($parent_acl && $acl['sub'] == 0) continue;

                //print $acl['rsn'].', '.$acl['code'] .' == '. $current_code.'<br/>';
                if ($acl['constraint_type'] == 2 &&
                    ($objectItem = krynObjects::get($pObjectKey, $current_code))){
                    if (!krynObjects::complies($objectItem, $acl['constraint_code'])) continue;
                }

                if (
                    $acl['constraint_type'] != 1 ||
                    ($acl['constraint_type'] == 1 && $acl['constraint_code'] == $current_code)
                ){

                    $fieldKey = $pField;

                    if ($pField){

                        if ($fIsArray && $fCount == 1){

                            if (is_string($fKey) && is_array($acl['fields'][$fKey])){
                                //this field has limits

                                if ( ($fieldAcl = $acl['fields'][$fKey]) !== null){

                                    if (is_array($fieldAcl[0])){

                                        foreach ($fieldAcl as $fRule){

                                            $uri = $fields[$fKey]['object'].'/'.$fValue;
                                            $satisfy = krynObjects::satisfyFromUri($uri, $fRule['condition']);
                                            if ($satisfy){
                                                return ($fRule['access'] == 1) ? true : false;
                                            }
                                            if ($acl['access'] != 2)
                                                return ($acl['access'] == 1) ? true : false;
                                            //var_dump(array($uri => $satisfy));

                                        }

                                    } else {

                                        if ($fieldAcl[$fValue] !== null){
                                            return ($fieldAcl[$fValue] == 1) ? true : false;
                                        } else {
                                            //current($pField) is not exactly defined in $fieldAcl, so we set $access to $acl['access']
                                            //
                                            //if access = 2 then wo do not know it, cause 2 means 'inherited', so maybe
                                            //a other rule has more detailed rule
                                            if ($acl['access'] != 2)
                                                return ($acl['access'] == 1) ? true : false;
                                        }
                                    }
                                }
                            } else {
                                //this field has only true or false
                                $fieldKey = $fKey;
                            }
                        }

                        if(!is_array($fieldKey)){
                            if ($acl['fields'] && ($fieldAcl = $acl['fields'][$fieldKey]) !== null && !is_array($acl['fields'][$fieldKey])){
                                return ($fieldAcl == 1) ? true : false;
                            } else {
                                //$pField is not exactly defined, so we set $access to $acl['access']
                                //and maybe a rule with the same code has the field defined
                                // if access = 2 then this rule is only for exactly define fields
                                if ($acl['access'] != 2)
                                    return ($acl['access'] == 1) ? true : false;
                            }
                        }
                    } else {
                        return ($acl['access'] == 1) ? true : false;
                    }
                }
            }

            if ($definition['nested'] ){
                if (!$current_code = krynObjects::getParentId($pObjectKey, $current_code)){
                    return $pRootHasAccess?true:$access;
                }

                $parent_acl = true;
            } else {
                break;
            }
        }

        self::$cache['checkAckl_' . $pObjectId . '__' . $pField] = $access;

        return $access;
    }


    /**
     *
     * Returns the acl infos for the specified id
     *
     * @param string  $pObject
     * @param integer $pCode
     *
     * @return array
     * @internal
     */
    public static function &getItem($pObject, $pCode) {

        self::normalizeCode($pCode);
        $acls =& self::getRules($pObject);

        foreach ($acls as $item) {
            $code = str_replace('%', '', $item['code']);
            $t = explode('[', $code);
            $code = $t[0];
            self::normalizeCode($code);
            if ($code == $pCode) {
                return $item;
            }
        }

        return false;
    }


    public static function set($pType, $pTargetType, $pTargetId, $pCode, $pActions, $pWithSub) {

        self::normalizeCode($pCode);
        $pType += 0;
        $pTargetType += $pTargetType;
        $pTargetId += $pTargetId;
        $pCode = esc($pCode);

        self::removeAcl($pType, $pTargetType, $pTargetId, $pCode);

        if ($pWithSub)
            $pCode .= '%';

        $pCode = '[' . implode(',', $pActions) . ']';

        $last_id = dbInsert('system_acl', array(
            'type' => $pType,
            'target_type' => $pTargetType,
            'target_rsn' => $pTargetId,
            'code' => $pCode
        ));

        self::$cache[$pType] = null;

        return $last_id;
    }

    public static function remove($pType, $pTargetType, $pTargetId, $pCode) {

        self::normalizeCode($pCode);

        $pType += 0;
        $pTargetType += $pTargetType;
        $pTargetId += $pTargetId;
        $pCode = esc($pCode);

        dbDelete('system_acl', "1=1 
         AND type = $pType
         AND target_type = $pTargetType
         AND target_rsn = $pTargetId
         AND code LIKE '$pCode%'");

        self::$cache[$pType] = null;

    }

    public static function normalizeCode(&$pCode) {

        $pCode = str_replace('//', '/', $pCode);

        if (substr($pCode, 0, 1) != '/')
            $pCode = '/' . $pCode;

        if (substr($pCode, -1) == '/')
            $pCode = substr($pCode, 0, -1);

    }

}

?>