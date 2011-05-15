<?php
global $CFG,$DB;
require_once($CFG->dirroot."/lib/dml/moodle_database.php");
require_once("./judge/ideone/ideone.php");
require_once($CFG->dirroot."/mod/assignment/type/onlinejudge/assignment.class.php");

class judge_base
{
	var $onlinejudge;
	/**
     * Returns an array of installed programming languages indexed and sorted by name
     */
	function get_languages(){}
	
      /**
     * Get one unjudged submission and set it as judged
     * If all submissions have been judged, return false
     * The function can be reentranced
     */
    function get_unjudged_submission() 
    {
        //try to obtain or release the cron lock.
        while (!set_cron_lock('task_judging', time() + 10)) {}
        //set_cron_lock('assignment_judging', time()+10);
        //query the unjudged data from table.
        $sql = 'SELECT 
                    id, taskid, judged '.
               'FROM '
                    .$CFG->prefix.'onlinejudge_task AS task, '
                    .$CFG->prefix.'onlinejudge_result AS result '.
               'WHERE '.
                    'task.id = result.taskid '.
                    'AND result.judged = 0 ';

        $submissions = $DB->get_records_sql($sql, '', 1);
        $submission = null;
        if ($submissions) {
            $submission = array_pop($submissions);
            // Set judged mark
            $DB->set_field('onlinejudge_result', 'judged', 1, 'id', $submission->taskid);
        }

        set_cron_lock('task_judging', null);

        return $submission;
    }
    
    /**
     * 
     * function diff() compare the output and the answer 
     */  
    function diff($answer, $output) {
        $answer = strtr(trim($answer), array("\r\n" => "\n", "\n\r" => "\n"));
        $output = trim($output);

        if (strcmp($answer, $output) == 0)
            return 'ac';
        else 
        {
            $tokens = array();
            $tok = strtok($answer, " \n\r\t");
            while ($tok) 
            {
                $tokens[] = $tok;
                $tok = strtok(" \n\r\t");
            }

            $tok = strtok($output, " \n\r\t");
            foreach ($tokens as $anstok) 
            {
                if (!$tok || $tok !== $anstok)
                    return 'wa';
                $tok = strtok(" \n\r\t");
            }

            return 'pe';
        }
    }
    
    /**
	 * 
	 * judge in sandbox or in ideone.
	 */
    function judge($sub){}
}
    

/*利用设计模式中的工厂模式来设计一个类，这个类根据id值的不同来选择创建不同
 *的ideone或sandbox实例
 */
class judge_factory
{
    	
    /*
     * 函数get_judge_methods列出可以使用的编译器语言的id，
     * 然后用户通过提供id值来进行以后的编译操作。 
     */
    function get_judge_methods()
    {
        echo "本系统支持的编译语言以及id值如下：<br>";
        $judge_methods_temp = array_flip($this->judge_methods);
        foreach($judge_methods_temp as $key=>$value)
        {
            //打印键值对，这里后期会利用语言来给每一个编译器提供注释，待完善。
            echo "$key----------$value<br>";
        }
        echo "<br><br><br>";
    }
	
    /*
     * 函数get_judge根据传入的id值来创建judge_ideone或者judge_sandbox对象
     */
    function get_judge($id)
    {	
        //检测id值是否在支持的编译器以及语言里
        if(in_array("$id", $judge_methods))
        {
            //选择的为sandbox的引擎以及语言
            if(id<=2)
            {
                $judge_obj = new judge_sandbox();
                $judge_obj->judge($sub);
            }
            //选择的为ideone的引擎以及语言
            else if(id>2 && id<53)
            {
                $judge_obj = new judge_ideone();
                //先对judge_methods进行翻译
                $judge_obj->translate($judge_methods);
                $judge_obj->judge($sub);
            }
        }
        //提示出错，重新传入id值
        else
        {	
            echo "所选择的语言不支持，请重新选择.<br>";
        }
    }	
}
?>