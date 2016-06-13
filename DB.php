<?php
/*
 *  +----------------------------------------------------------------------
 *  | The author ( King east )
 *  +----------------------------------------------------------------------
 *  | Licensed ( http://www.scirpt.wang/ )
 *  +----------------------------------------------------------------------
 *  | Author: King east <1207877378@qq.com>
 *  +----------------------------------------------------------------------
 */
/**
 * Created by PhpStorm.
 * User: wzdon
 * Date: 2016/5/10
 * Time: 17:53
 */
namespace ke;
/**
 * class:ke
 *
 * @author King east
 */
class db{
    private $db=null;
    private $st=null;
    private $i=0;
    private $key=[];
    private $config=[
        'host'=>'',
        'name'=>'',
        'pre'=>'ke_',
        'user'=>'',
        'pass'=>'',
        'charset'=>'utf8',
        'debug'=>true
    ];
    private $sql=null;
    private $option=[
        'field'=>'*',
        'where'=>[],
        'whereS'=>null,
    ];
    private $if=['AND','OR'];
    private $condition=['=','!=','>','<','<=','>=','like'];
    private $dbs=null;
    public function __construct($config=null){
        if(is_null($config)){
            $config= include APP_PATH.'common/config/database.php';
        }
        $this->config=array_merge($this->config,$config);
        $this->connect();
        //print_r($this->config);
        //echo '<br/>';
    }
    private function connect(){
        try{
            //print_r($this->config);
            $this->db=new \PDO('mysql:host='.$this->config['host'].';dbname='.$this->config['name'],$this->config['user'],$this->config['pass']);
            $this->db->exec('SET NAMES \''.$this->config['charset'].'\';');
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }catch (\PDOException $e){
            echo $e->getMessage();
            exit();
        }
    }
    public function table($name){
        $this->option['table']=$this->config['pre'].$name;
        return $this;
    }
    /*
     * where解析
     */
    public function where($str=null){
        $this->option['where'][]=$str;
        return $this;
    }
    /*
     * data
     */
    public function data($str){
        $this->option['data']=$str;
        return $this;
    }
    /*
     * field
     */
    public function field($str){
        $str=preg_replace('/([\w]+)/','`'.$this->option['table'].'`.`\\1`',$str);
        $this->option['field']=$str;
        return $this;
    }
    /*
     * ORDER
     */
    public function order($str){
        $this->option['order']=$str;
        return $this;
    }
    public function limit($str){
        $this->option['limit']='LIMIT '.$str;
        return $this;
    }
    private function parseData($type=0){
        $key=[];
        $value=[];
        if($type===0){
            $data='(';
            if(is_array($this->option['data'])){
                foreach ($this->option['data'] as $keys=>$values){
                    $key[]=$keys;
                    $value[]=$this->parseValue($values);
                }
                $data.='`'.implode('`,`',$key).'`) VALUES ('.implode(',',$value).')';
            }
        }else{
            $data=null;
            if(is_array($this->option['data'])){
                foreach ($this->option['data'] as $keys=>$values){
                    $key[]='`'.$keys.'`='.$this->parseValue($values).'';
                }
                $data=implode(',',$key);
            }
        }
        return $data;
    }
    private function parseWhere(){
        $is=1;
        if(is_string($this->option['where'])){
            $this->option['whereS'].=$this->option['where'];
        }else{
            if(empty($this->option['where'])){
                return;
            }
            $where='(';
            foreach ($this->option['where'] as $list){
                $i=0;
                $logic=strtoupper($list[0]);
                if(in_array($logic,$this->if)){
                    //第一次层where不加运算符
                    if(empty($this->option['whereS'])){
                        $where.=' '.$logic.' ';
                    }
                    $i=1;
                }else{
                    if(!$is){
                        $where.=' AND ';
                    }
                }
                $where.='`'.$this->option['table'].'`.`'.$list[$i].'`';
                if(in_array($list[$i+1],$this->condition)){
                    $where.=' '.$list[$i+1].' '.$this->parseValue($list[$i+2]);
                }else{
                    $where.=' = '.$this->parseValue($list[$i+1]);
                }
                $is=0;
            }
            $this->option['whereS'].=$where.')';
        }
    }
    /*
     * join
     */
    public function join($str){
        return $this;
    }
    /*
     * 绑定变量
     */
    public function bind($i,$str){
        $this->key[$i-1]=$str;
    }
    /*
     * 执行一条sql语句
     */
    public function run($sql,$return=false){
        try{
            $this->dbs=$this->db->prepare($sql);
            foreach ($this->key as $key=>$value){
                $this->dbs->bindValue($key+1,$value,$this->getType($value));
            }
            unset($this->option['data']);
            unset($this->option['where']);
            unset($this->key);
            unset($this->sql);
            if($return){
                if($this->dbs->execute()){
                    return $this->dbs;
                }else{
                    return $this->dbs->execute();
                }
            }else{
                return $this->dbs->execute();
            }
        }catch (\PDOException $e){
            if($this->config['debug']){
                echo '<h2>PHPFILE:'.$e->getFile().'</h2>';
                echo '<h4>ERRORFINE:'.$e->getLine().'</h4>';
                exit('<span style="color:red;">'.$e->getMessage().'</span>');
            }else{
                exit('系统内部错误:'.$e->getCode());
            }
        }
    }
    /*
     * value解析
     */
    private function parseValue($str){
        $this->key[]=$str;
        return '?';
    }
    /*
     * 组合sql
     */
    private function gsql($type=0){
        switch ($type){
            case 1:
                $this->sql='INSERT INTO ';
                if(empty($this->option['table'])){
                    exit('请输入table');
                }
                $this->sql.='`'.$this->option['table'].'` ';
                if(empty($this->option['data'])){
                    exit('请输入data');
                }
                $this->sql.=$this->parseData(0).' ';
                break;
            case 2:
                $this->sql='UPDATE ';
                if(empty($this->option['table'])){
                    exit('请输入table');
                }
                $this->sql.='`'.$this->option['table'].'` SET ';
                if(empty($this->option['data'])){
                    exit('请输入data');
                }
                $this->sql.=$this->parseData(1).' ';
                $this->parseWhere();
                if(isset($this->option['whereS'])){
                    $this->sql.=' WHERE '.$this->option['whereS'];
                }
                break;
            case 3:
                $this->sql='DELETE FROM ';
                if(empty($this->option['table'])){
                    exit('请输入table');
                }
                $this->sql.='`'.$this->option['table'].'` ';
                $this->parseWhere();
                if(isset($this->option['whereS'])){
                    $this->sql.=' WHERE '.$this->option['whereS'];
                }
                break;
            case 4:
                $this->sql='SELECT ';
                $this->sql.=' count(*) FROM ';
                if(empty($this->option['table'])){
                    exit('请输入table');
                }
                $this->sql.='`'.$this->option['table'].'` ';
                $this->parseWhere();
                if(!is_null($this->option['whereS'])){
                    $this->sql.=' WHERE '.$this->option['whereS'];
                }
                break;
            default:
                $this->sql='SELECT ';
                $this->sql.=$this->option['field'].' FROM ';
                if(empty($this->option['table'])){
                    exit('请输入table');
                }
                $this->sql.='`'.$this->option['table'].'` ';
                $this->parseWhere();
                if(!is_null($this->option['whereS'])){
                    $this->sql.=' WHERE '.$this->option['whereS'];
                }
                if(isset($this->option['order'])){
                    $this->sql.=' ORDER BY '.$this->option['order'];
                }
                if(isset($this->option['limit'])){
                    $this->sql.=' '.$this->option['limit'];
                }
                break;
        }
    }
    /*
     * 获取最后插入的ID值
     */
    public function getid(){
        return $this->db->lastInsertId();
    }
    private function getType($value){
        switch ($value){
            case is_int($value):
                $param = \PDO::PARAM_INT;
                break;
            case is_integer($value):
                $param = \PDO::PARAM_INT;
                break;
            case is_bool($value):
                $param = \PDO::PARAM_BOOL;
                break;
            case is_null($value):
                $param = \PDO::PARAM_NULL;
                break;
            case is_string($value):
                $param = \PDO::PARAM_STR;
                break;
            default:
                $param=false;
                break;
        }
        return $param;
    }
    public function sql($type=0){
        $this->gsql($type);
        $sql=$this->sql;
        $r=$this->db->prepare($sql);
        unset($this->sql);
        unset($this->key);
        ob_start();
        $r->debugDumpParams();
        $r = ob_get_contents();
        ob_end_clean();
        echo $r;
    }
    /*
     * 统计行数
     */
    public function count(){
        $this->gsql(4);
        $this->run($this->sql);
        $row=$this->dbs->fetch();
        return $row[0];
    }
    /*
     * 添加
     */
    public function add(){
        $this->gsql(1);
        if($this->run($this->sql)){
            return $this->getid();
        }else{
            return false;
        }
    }
    /*
     * 更新
     */
    public function edit(){
        $this->gsql(2);
        return $this->run($this->sql);
    }
    /*
     * 删除
     */
    public function del(){
        $this->gsql(3);
        return $this->run($this->sql);
    }
    /*
     * 获取全部
     */
    public function all(){
        $this->gsql(0);
        $this->run($this->sql,true);
        if($this->dbs){
            $list=$this->dbs->fetchAll(\PDO::FETCH_ASSOC);
            return $list;
        }else{
            return false;
        }
    }
    /*
     * 获取多条或单条
     * 单条则返回一维数组
     */
    public function find($num=1){
        $this->gsql(0);
        $rs=$this->run($this->sql,true);
        if($rs){
            if($num>1 || $num==0){
                $list=$rs->fetchAll(\PDO::FETCH_ASSOC);
            }else{
                $list=$rs->fetch(\PDO::FETCH_ASSOC);
            }
            return $list;
        }else{
            return false;
        }
    }
}
