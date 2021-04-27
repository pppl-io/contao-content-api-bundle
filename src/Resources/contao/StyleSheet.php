<?php

namespace DieSchittigs\ContaoContentApiBundle;

use DieSchittigs\ContaoContentApiBundle\ApiStyle;

use Contao\StyleModel;
use Contao\StyleSheetModel;
use Contao\StyleSheets;
/**
 * ApiStyleSheet augments StyleSheetModel for the API.
 */
class ApiStyleSheet extends AugmentedContaoModel
{
    /**
     * constructor.
     *
     * @param int $id id of the StyleSheetModel
     */
    public function __construct($id)
    {
        $stylesheet = StyleSheetModel::findById($id);

        $this->model = $stylesheet;

        $this->styles = ApiStyle::list($id);


        //Dirty :(
        $compiledCss = "";
        $cStyles = new StyleSheets();
        $styles = StyleModel::findByPid($id);
        foreach($styles as $style) {
            
            $compiledCss .= $cStyles->compileDefinition($style->row(), false, $this->vars, $stylesheet->row(), true);
        }
        $this->compiledCss = $compiledCss;
    }


    public static function list($pid)
    {
        $stylesheets = [];

        $dbStylesheets = $pid > 0 ? StyleSheetModel::findByPid($pid) : StyleSheetModel::findAll();

        foreach ($dbStylesheets as $stylesheet) {
            $stylesheets[] = new self($stylesheet->id);
        }

        return $stylesheets;
    }

    public static function listAction($pid)
    {
        return new ContaoJson(self::list($pid));
    }
}
