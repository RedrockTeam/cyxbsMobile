<?php

namespace Home\Common;

use Closure;

require "PHPExcel.php";

class ExcelGeneratorController
{

    /** @var PHPExcel|null */
    public $excel_generator = null;

    /** @var PHPExcel_DocumentProperties */
    protected $property;

    /** @var PHPExcel_Worksheet */
    protected $sheet;

    /** @var PHPExcel_Style */
    protected $style;

    /**
     * ExcelGeneratorController constructor.
     *
     */
    public function __construct()
    {
        $this->excel_generator = new \PHPExcel();
    }

    /**
     * 提供设置Excel的相关属性,包括了比如
     * 标题,描述等属性
     *
     * @return ExcelGeneratorController
     */
    protected function _property()
    {
        $this->property = $this->excel_generator->getProperties();

        return $this;
    }

    /**
     * 提供操作Excel列表表格等相关属性,包括取值等
     *
     * @return ExcelGeneratorController
     */
    protected function _sheet()
    {
        $this->sheet = $this->excel_generator->getActiveSheet();

        return $this;
    }

    /**
     * 提供设置Excel的相关风格属性
     *
     * @return ExcelGeneratorController
     */
    protected function _style()
    {
        $this->style = $this->excel_generator->getDefaultStyle();

        return $this;
    }

    /**
     * 读取默认的模板XLS(X)文件
     *
     * @param string $type
     * @param string $filepath
     *
     * @return \PHPExcel
     * @throws \PHPExcel_Reader_Exception
     */
    public function reader($type = 'Excel5', $filepath = '')
    {
        $reader = \PHPExcel_IOFactory::createReader($type);

        return $reader->load(__DIR__ . '/' . $filepath);
    }

    /**
     * 将生成的XLS(X)文件导出
     *
     * @param \PHPExcel $excel
     * @param string    $option
     *
     * @return \PHPExcel_Writer_IWriter
     * @throws \PHPExcel_Reader_Exception
     */
    public function writer(\PHPExcel $excel, $option = 'Excel5')
    {
        return \PHPExcel_IOFactory::createWriter($excel, $option);
    }

    /**
     * 提供一种简便的统一的调用方法
     *
     * @example $this->property()->Creator()->LastModify();
     *
     * @param string $name      一个简单的命令
     * @param mixed  $arguments 该命令所需要的参数
     *
     * @return mixed
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    function __call($name, $arguments)
    {
        $_name = '_' . $name;

        /** @var string Excel类的子类名 */
        if (!array_key_exists($name, get_class_vars(get_called_class()))) {
            throw new \InvalidArgumentException;
        }

        $this->$_name();

        /** @var Clousure|string $arguments[0] 提供一个用于执行excel各子类的方法 */
        if (!is_callable($arguments[0])) {
            if (method_exists($this->$name, $arguments[0])) {
                call_user_func_array(array($this->$name, $arguments[0]), array_intersect($arguments, array($arguments[0])));

                return $this;
            }
            throw new \RuntimeException;
        }

        /** @var Closure $callable */
        $callable = $arguments[0];

        return $callable($this->$name);
    }

}